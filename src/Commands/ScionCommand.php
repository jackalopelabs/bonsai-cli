<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ScionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('scion')
            ->setDescription('Reverse-engineer a Roots template into a Bonsai CLI YAML configuration')
            ->addArgument('templateName', InputArgument::REQUIRED, 'The name of the template to generate')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'The source template file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Retrieve the source file path
        $source = $input->getOption('source');
        if (!$source || !file_exists($source)) {
            $output->writeln("<error>Please provide a valid source file path using the --source option.</error>");
            return Command::FAILURE;
        }

        // Get the template name
        $templateName = $input->getArgument('templateName');
        $output->writeln("<info>Starting extraction for template: {$templateName}</info>");

        // Create component directory
        $componentDir = __DIR__ . '/../../templates/components/' . $templateName;
        if (!is_dir($componentDir)) {
            mkdir($componentDir, 0755, true);
            $output->writeln("<info>Created component directory: {$componentDir}</info>");
        }

        // Read the template file
        $content = file_get_contents($source);

        // Extract all Blade @include directives
        preg_match_all("/@include\(['\"]([^'\"]+)['\"]\)/", $content, $matches);
        $includes = $matches[1] ?? [];

        if (empty($includes)) {
            $output->writeln("<error>No include directives found in the template.</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Found the following includes:</info>");
        foreach ($includes as $include) {
            $output->writeln(" - " . $include);
        }

        // For each include, analyze the section and extract its components
        $helper = $this->getHelper('question');
        $sections = [];
        
        foreach ($includes as $include) {
            // Convert include path to file path
            $sectionPath = $this->convertIncludeToPath($source, $include);
            if (!file_exists($sectionPath)) {
                $output->writeln("<error>Section file not found: {$sectionPath}</error>");
                continue;
            }

            // Extract the last part of the include path as the default key
            $parts = explode('.', $include);
            $defaultKey = end($parts);
            
            $sectionKeyQuestion = new Question("Enter section key for include '{$include}' [{$defaultKey}]: ", $defaultKey);
            $sectionKey = $helper->ask($input, $output, $sectionKeyQuestion);

            // Analyze section file to find component usage
            $componentInfo = $this->analyzeSectionComponents($sectionPath, $output);
            if (empty($componentInfo)) {
                $output->writeln("<error>No components found in section: {$sectionPath}</error>");
                continue;
            }

            // Copy each component used in the section
            foreach ($componentInfo as $componentName => $componentData) {
                $this->copyComponentToTemplate($componentName, $componentDir, $output);
            }

            $sections[$sectionKey] = [
                'components' => $componentInfo,
            ];
        }

        // Ask the user for layout order
        $defaultOrder = implode(',', array_keys($sections));
        $orderQuestion = new Question("Enter comma-separated section keys for layout order [{$defaultOrder}]: ", $defaultOrder);
        $orderInput = $helper->ask($input, $output, $orderQuestion);
        $layoutSections = array_map('trim', explode(',', $orderInput));

        // Build the configuration array
        $config = [
            'sections' => $sections,
            'layout'   => [
                'sections' => $layoutSections,
            ],
        ];

        // Dump the configuration array to YAML
        $yamlContent = Yaml::dump($config, 4, 2);

        // Save the YAML file to config/bonsai/templates/{templateName}.yml
        $targetPath = __DIR__ . '/../../config/bonsai/templates/' . $templateName . '.yml';

        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0755, true);
        }

        file_put_contents($targetPath, $yamlContent);

        $output->writeln("<info>Configuration saved to: {$targetPath}</info>");
        $output->writeln("<info>Components created in: {$componentDir}</info>");
        $output->writeln("<info>Now run 'wp acorn bonsai:generate {$templateName}' in your Roots project to generate the landing page.</info>");

        return Command::SUCCESS;
    }

    private function convertIncludeToPath(string $templatePath, string $include): string
    {
        // Get the resources/views path by removing everything after and including /bonsai/
        $viewsPath = preg_replace('/\/bonsai\/.*$/', '', dirname($templatePath));
        
        // Convert dot notation to directory structure
        $parts = explode('.', $include);
        
        // If it starts with 'bonsai', handle it specially
        if ($parts[0] === 'bonsai') {
            array_shift($parts); // Remove 'bonsai'
            return $viewsPath . '/bonsai/' . implode('/', $parts) . '.blade.php';
        }
        
        // For non-bonsai paths
        return $viewsPath . '/' . implode('/', $parts) . '.blade.php';
    }

    private function analyzeSectionComponents(string $sectionPath, OutputInterface $output): array
    {
        $content = file_get_contents($sectionPath);
        $components = [];

        // Look for x-bonsai::cypress style components
        preg_match_all("/<x-bonsai::([^\\s>]+)/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $component) {
                $components[$component] = [
                    'type' => 'x-bonsai',
                    'name' => $component,
                    'data' => $this->extractComponentData($content),
                ];
            }
        }

        // Look for standard x-component tags
        preg_match_all("/<x-([^\\s>:]+)/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $component) {
                $components[$component] = [
                    'type' => 'x-component',
                    'name' => $component,
                    'data' => $this->extractComponentData($content),
                ];
            }
        }

        // Look for @include directives that reference components
        preg_match_all("/@include\(['\"]([^'\"]+)['\"]/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $include) {
                if (strpos($include, 'components.') === 0) {
                    $componentName = str_replace('components.', '', $include);
                    $components[$componentName] = [
                        'type' => 'include',
                        'name' => $componentName,
                        'data' => $this->extractComponentData($content),
                    ];
                }
            }
        }

        return $components;
    }

    private function extractComponentData(string $content): array
    {
        $data = [];
        
        // Look for PHP arrays with component data
        if (preg_match('/\$[a-zA-Z_]+Data\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            $arrayContent = $matches[1];
            
            // Extract key-value pairs
            preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $arrayContent, $pairs);
            if (!empty($pairs[1])) {
                for ($i = 0; $i < count($pairs[1]); $i++) {
                    $key = $pairs[1][$i];
                    $value = $pairs[2][$i];
                    $data[$key] = $value;
                }
            }

            // Extract nested arrays (like iconMappings)
            if (preg_match("/'iconMappings'\s*=>\s*\[(.*?)\]/s", $arrayContent, $iconMatches)) {
                $iconContent = $iconMatches[1];
                preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $iconContent, $iconPairs);
                if (!empty($iconPairs[1])) {
                    $data['iconMappings'] = [];
                    for ($i = 0; $i < count($iconPairs[1]); $i++) {
                        $key = $iconPairs[1][$i];
                        $value = $iconPairs[2][$i];
                        $data['iconMappings'][$key] = $value;
                    }
                }
            }
        }

        return $data;
    }

    private function copyComponentToTemplate(string $componentName, string $targetDir, OutputInterface $output): void
    {
        // Handle cypress-specific components
        if (strpos($componentName, 'cypress.') === 0) {
            $componentName = str_replace('cypress.', '', $componentName);
        }

        // First try to find the component in various possible locations
        $sourcePaths = [
            __DIR__ . '/../../templates/components/cypress/' . $componentName . '.blade.php',
            __DIR__ . '/../../templates/components/' . $componentName . '.blade.php',
            __DIR__ . '/../../resources/views/components/' . $componentName . '.blade.php',
            __DIR__ . '/../../resources/views/bonsai/components/' . $componentName . '.blade.php',
        ];

        $sourceFile = null;
        foreach ($sourcePaths as $path) {
            if (file_exists($path)) {
                $sourceFile = $path;
                break;
            }
        }

        if (!$sourceFile) {
            $output->writeln("<error>Component not found: {$componentName} (searched in standard locations)</error>");
            return;
        }

        // Copy the component to the template-specific directory
        $targetFile = $targetDir . '/' . basename($sourceFile);
        copy($sourceFile, $targetFile);
        $output->writeln("<info>Copied component {$componentName} to template directory</info>");
    }
} 