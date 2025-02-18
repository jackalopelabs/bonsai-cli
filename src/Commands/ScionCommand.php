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

            // Get the primary component and its data
            $primaryComponent = $this->getPrimaryComponent($componentInfo);
            if ($primaryComponent) {
                // Ensure we use the template namespace for components
                $componentName = $primaryComponent['name'];
                if (strpos($componentName, $templateName . '.') !== 0 && strpos($componentName, 'bonsai::' . $templateName . '.') === false) {
                    $componentName = $templateName . '.' . $componentName;
                }
                
                $sections[$sectionKey] = [
                    'component' => $componentName,
                    'data' => $primaryComponent['data'],
                ];

                $output->writeln("<info>Using component {$componentName} for section {$sectionKey}</info>");
            }
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
        $output->writeln("<info>Now run 'wp acorn bonsai:generate {$templateName}' in your Roots project to generate the landing page.</info>");

        return Command::SUCCESS;
    }

    private function convertIncludeToPath(string $templatePath, string $include): string
    {
        // Get the base views path from the template path
        $viewsPath = dirname(dirname(dirname($templatePath))); // Go up 3 levels from template file
        
        // Convert dot notation to directory structure
        $parts = explode('.', $include);
        
        // Handle different path patterns
        if ($parts[0] === 'bonsai') {
            array_shift($parts); // Remove 'bonsai'
            $path = $viewsPath . '/bonsai/' . implode('/', $parts) . '.blade.php';
            
            // Try alternate paths if file doesn't exist
            if (!file_exists($path)) {
                $alternatePaths = [
                    $viewsPath . '/resources/views/bonsai/' . implode('/', $parts) . '.blade.php',
                    dirname($templatePath) . '/' . implode('/', $parts) . '.blade.php',
                ];
                
                foreach ($alternatePaths as $altPath) {
                    if (file_exists($altPath)) {
                        return $altPath;
                    }
                }
            }
            
            return $path;
        }
        
        // For non-bonsai paths
        return $viewsPath . '/' . implode('/', $parts) . '.blade.php';
    }

    private function analyzeSectionComponents(string $sectionPath, OutputInterface $output): array
    {
        if (!file_exists($sectionPath)) {
            $output->writeln("<comment>Warning: Section file not found at {$sectionPath}, trying alternate locations...</comment>");
            
            // Try alternate locations
            $alternatePaths = [
                str_replace('/resources/views/', '/', $sectionPath),
                str_replace('/bonsai/', '/resources/views/bonsai/', $sectionPath),
            ];
            
            foreach ($alternatePaths as $path) {
                if (file_exists($path)) {
                    $sectionPath = $path;
                    $output->writeln("<info>Found section file at: {$path}</info>");
                    break;
                }
            }
            
            if (!file_exists($sectionPath)) {
                $output->writeln("<error>Could not find section file in any location</error>");
                return [];
            }
        }

        $content = file_get_contents($sectionPath);
        $components = [];

        // Look for x-bonsai::cypress style components first (highest priority)
        preg_match_all("/<x-bonsai::([^\\s>]+)/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $component) {
                if (!$this->shouldSkipComponent($component)) {
                    // For cypress.hero style components, preserve the full name
                    $components[$component] = [
                        'type' => 'x-bonsai',
                        'name' => $component, // Keep full name like 'cypress.hero'
                        'data' => $this->extractComponentData($content),
                        'priority' => 1,
                    ];
                    $output->writeln("<info>Found bonsai component: {$component}</info>");
                }
            }
        }

        // Look for x-component tags (medium priority)
        preg_match_all("/<x-([^\\s>:]+)/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $component) {
                if (!$this->shouldSkipComponent($component)) {
                    // Check if this is a namespaced component (e.g., cypress.hero)
                    $components[$component] = [
                        'type' => 'x-component',
                        'name' => $component,
                        'data' => $this->extractComponentData($content),
                        'priority' => 2,
                    ];
                    $output->writeln("<info>Found component: {$component}</info>");
                }
            }
        }

        // Look for @include directives that reference components (lowest priority)
        preg_match_all("/@include\(['\"]([^'\"]+)['\"]/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $include) {
                if (strpos($include, 'components.') === 0) {
                    $componentName = str_replace('components.', '', $include);
                    if (!$this->shouldSkipComponent($componentName)) {
                        $components[$componentName] = [
                            'type' => 'include',
                            'name' => $componentName,
                            'data' => $this->extractComponentData($content),
                            'priority' => 3,
                        ];
                        $output->writeln("<info>Found included component: {$componentName}</info>");
                    }
                }
            }
        }

        return $components;
    }

    private function shouldSkipComponent(string $component): bool
    {
        // Skip generic bonsai component
        if ($component === 'bonsai') {
            return true;
        }

        // Skip Heroicon components
        if (strpos($component, 'heroicon-') === 0) {
            return true;
        }

        return false;
    }

    private function getPrimaryComponent(array $components): ?array
    {
        if (empty($components)) {
            return null;
        }

        // Sort components by priority (lower number = higher priority)
        uasort($components, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // Return the first (highest priority) component
        return reset($components);
    }

    private function extractComponentData(string $content): array
    {
        $data = [];
        
        // Look for PHP arrays with component data
        if (preg_match('/\$[a-zA-Z_]+Data\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            $arrayContent = $matches[1];
            
            // Extract key-value pairs, including string literals and booleans
            preg_match_all("/'([^']+)'\s*=>\s*(?:'([^']+)'|true|false|\[([^\]]+)\])/", $arrayContent, $pairs);
            if (!empty($pairs[1])) {
                for ($i = 0; $i < count($pairs[1]); $i++) {
                    $key = $pairs[1][$i];
                    $value = $pairs[2][$i];
                    
                    // Handle boolean values
                    if ($value === '') {
                        if (strpos($pairs[0][$i], '=> true') !== false) {
                            $value = true;
                        } elseif (strpos($pairs[0][$i], '=> false') !== false) {
                            $value = false;
                        }
                    }
                    
                    // Handle nested arrays (like iconMappings)
                    if (empty($value) && !empty($pairs[3][$i])) {
                        $nestedArray = [];
                        preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $pairs[3][$i], $nestedPairs);
                        if (!empty($nestedPairs[1])) {
                            for ($j = 0; $j < count($nestedPairs[1]); $j++) {
                                $nestedArray[$nestedPairs[1][$j]] = $nestedPairs[2][$j];
                            }
                        }
                        $value = $nestedArray;
                    }
                    
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    private function copyComponentToTemplate(string $componentName, string $targetDir, OutputInterface $output): void
    {
        // Handle nested components (e.g., cypress.hero)
        $parts = explode('.', $componentName);
        $componentPath = implode('/', $parts);
        
        // First try to find the component in various possible locations
        $sourcePaths = [
            __DIR__ . '/../../templates/components/' . $componentPath . '.blade.php',
            __DIR__ . '/../../resources/views/components/' . $componentPath . '.blade.php',
            __DIR__ . '/../../resources/views/bonsai/components/' . $componentPath . '.blade.php',
            // Fallback to non-nested paths
            __DIR__ . '/../../templates/components/' . end($parts) . '.blade.php',
            __DIR__ . '/../../resources/views/components/' . end($parts) . '.blade.php',
            __DIR__ . '/../../resources/views/bonsai/components/' . end($parts) . '.blade.php',
        ];

        $sourceFile = null;
        foreach ($sourcePaths as $path) {
            if (file_exists($path)) {
                $sourceFile = $path;
                break;
            }
        }

        if (!$sourceFile) {
            $output->writeln("<error>Component not found: {$componentName} (searched in: " . implode(', ', $sourcePaths) . ")</error>");
            return;
        }

        // Create nested directory structure if needed
        if (count($parts) > 1) {
            $nestedDir = $targetDir . '/' . implode('/', array_slice($parts, 0, -1));
            if (!is_dir($nestedDir)) {
                mkdir($nestedDir, 0755, true);
                $output->writeln("<info>Created nested directory: {$nestedDir}</info>");
            }
            $targetFile = $nestedDir . '/' . end($parts) . '.blade.php';
        } else {
            $targetFile = $targetDir . '/' . $componentName . '.blade.php';
        }

        copy($sourceFile, $targetFile);
        $output->writeln("<info>Copied component {$componentName} to {$targetFile}</info>");
    }
} 