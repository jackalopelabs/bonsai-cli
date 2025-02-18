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
                // Clean up the component name to ensure single dot
                $componentName = $primaryComponent['name'];
                $parts = explode('.', $componentName);
                $baseComponent = end($parts);
                $componentName = $templateName . '.' . $baseComponent;
                
                $sections[$sectionKey] = [
                    'component' => $componentName,
                    'data' => $primaryComponent['data'],
                ];

                // Copy the component to the template directory
                $this->copyComponentToTemplate($componentName, $componentDir, $output);
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
        $output->writeln("<info>Components copied to: {$componentDir}</info>");
        $output->writeln("<info>Now run 'wp acorn bonsai:generate {$templateName}' in your Roots project to generate the landing page.</info>");

        return Command::SUCCESS;
    }

    private function convertIncludeToPath(string $templatePath, string $include): string
    {
        // Get the base views path from the template path
        $viewsPath = dirname(dirname(dirname($templatePath))); 
        
        // Split the include path into parts
        $parts = explode('.', $include);
        
        // Handle different path patterns
        if ($parts[0] === 'bonsai') {
            array_shift($parts); // Remove 'bonsai'
            
            // Check if this is a template-specific section (e.g., bonsai.sections.cypress.home_hero)
            if (count($parts) > 2 && $parts[0] === 'sections') {
                $templateName = $parts[1];  // e.g., 'cypress'
                $sectionName = end($parts); // e.g., 'home_hero'
                
                // First try to find it in the template's sections directory
                $templatePath = $viewsPath . '/' . $templateName . '/sections/' . $sectionName . '.blade.php';
                if (file_exists($templatePath)) {
                    return $templatePath;
                }
                
                // Then try the bonsai directory
                $bonsaiPath = $viewsPath . '/bonsai/sections/' . $sectionName . '.blade.php';
                if (file_exists($bonsaiPath)) {
                    return $bonsaiPath;
                }
                
                // Finally, try the template-specific directory in bonsai
                $bonsaiTemplatePath = $viewsPath . '/bonsai/sections/' . $templateName . '/' . $sectionName . '.blade.php';
                if (file_exists($bonsaiTemplatePath)) {
                    return $bonsaiTemplatePath;
                }
                
                // If not found, return the template path (it will be created there)
                return $templatePath;
            }
            
            // Default bonsai path handling
            $path = $viewsPath . '/bonsai/' . implode('/', $parts) . '.blade.php';
            if (file_exists($path)) {
                return $path;
            }
            
            // Try alternate paths
            $alternatePaths = [
                $viewsPath . '/resources/views/bonsai/' . implode('/', $parts) . '.blade.php',
                dirname($templatePath) . '/' . implode('/', $parts) . '.blade.php',
            ];
            
            foreach ($alternatePaths as $altPath) {
                if (file_exists($altPath)) {
                    return $altPath;
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

        // Extract template name from include path for template-specific sections
        if (preg_match('|bonsai\.sections\.([^\.]+)\.|', $sectionPath, $matches)) {
            $templateName = $matches[1];  // e.g., 'cypress' from bonsai.sections.cypress.home_hero
        } else {
            // Fallback to extracting from file path
            preg_match('|/([^/]+)/sections/|', $sectionPath, $matches);
            $templateName = $matches[1] ?? null;
            
            // If template name is 'bonsai', look for nested template name
            if ($templateName === 'bonsai') {
                preg_match('|/bonsai/sections/([^/]+)/|', $sectionPath, $matches);
                $templateName = $matches[1] ?? null;
            }
        }

        // Helper function to clean component names
        $cleanComponentName = function($component) use ($templateName) {
            // First remove any existing template prefixes and bonsai prefixes
            $baseComponent = preg_replace("/^{$templateName}\.+|^bonsai::\.+|^bonsai\.+/", '', $component);
            // Remove any remaining dots
            $baseComponent = preg_replace("/\.+/", '', $baseComponent);
            // Remove any remaining template prefix if it exists
            $baseComponent = preg_replace("/^{$templateName}/", '', $baseComponent);
            // Clean up any remaining whitespace
            $baseComponent = trim($baseComponent);
            
            // Return clean component name with single dot
            return rtrim($templateName . '.' . $baseComponent, '.');
        };

        // Look for x-bonsai:: components first (highest priority)
        preg_match_all("/<x-bonsai::([^\\s>]+)/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $component) {
                if (!$this->shouldSkipComponent($component)) {
                    $componentName = $cleanComponentName($component);
                    $components[$componentName] = [
                        'type' => 'x-bonsai',
                        'name' => $componentName,
                        'data' => $this->extractComponentData($content),
                        'priority' => 1,
                    ];
                    $output->writeln("<info>Found component: {$componentName}</info>");
                }
            }
        }

        // Look for x-component tags (medium priority)
        preg_match_all("/<x-([^\\s>:]+)/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $component) {
                if (!$this->shouldSkipComponent($component)) {
                    $componentName = $cleanComponentName($component);
                    $components[$componentName] = [
                        'type' => 'x-component',
                        'name' => $componentName,
                        'data' => $this->extractComponentData($content),
                        'priority' => 2,
                    ];
                    $output->writeln("<info>Found component: {$componentName}</info>");
                }
            }
        }

        // Look for @include directives that reference components (lowest priority)
        preg_match_all("/@include\(['\"]([^'\"]+)['\"]/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $include) {
                if (strpos($include, 'components.') === 0) {
                    $baseComponentName = str_replace('components.', '', $include);
                    if (!$this->shouldSkipComponent($baseComponentName)) {
                        $componentName = $cleanComponentName($baseComponentName);
                        $components[$componentName] = [
                            'type' => 'include',
                            'name' => $componentName,
                            'data' => $this->extractComponentData($content),
                            'priority' => 3,
                        ];
                        $output->writeln("<info>Found component: {$componentName}</info>");
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
            
            // Extract key-value pairs, including arrays and nested structures
            preg_match_all("/'([^']+)'\s*=>\s*(?:'([^']+)'|true|false|\[(.*?)\])/s", $arrayContent, $pairs);
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
                    
                    // Handle array fields that should always be arrays
                    if (in_array($key, ['imagePaths', 'featureItems', 'listItems', 'pricingBoxes', 'features'])) {
                        if (empty($pairs[3][$i])) {
                            $value = [];
                        } else {
                            $nestedArray = [];
                            preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $pairs[3][$i], $nestedPairs);
                            if (!empty($nestedPairs[1])) {
                                $item = [];
                                for ($j = 0; $j < count($nestedPairs[1]); $j++) {
                                    $item[$nestedPairs[1][$j]] = $nestedPairs[2][$j];
                                }
                                $value = [$item];
                            } else {
                                $value = [];
                            }
                        }
                    }
                    // Handle other nested arrays
                    elseif (empty($value) && !empty($pairs[3][$i])) {
                        $nestedArray = [];
                        preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $pairs[3][$i], $nestedPairs);
                        if (!empty($nestedPairs[1])) {
                            for ($j = 0; $j < count($nestedPairs[1]); $j++) {
                                $nestedArray[$nestedPairs[1][$j]] = $nestedPairs[2][$j];
                            }
                            $value = $nestedArray;
                        }
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
        
        // Get the template name and base component name
        $templateName = $parts[0];
        $baseComponentName = end($parts); // Get the base name (e.g., 'hero' from 'cypress.hero')
        
        // First try to find the component in various possible locations
        $sourcePaths = [
            // Try the package's templates directory first
            __DIR__ . '/../../templates/components/' . $baseComponentName . '.blade.php',
            // Try the source project's components directory (if available)
            dirname(dirname(dirname(__DIR__))) . '/resources/views/bonsai/components/' . $baseComponentName . '.blade.php',
            dirname(dirname(dirname(__DIR__))) . '/resources/views/components/' . $baseComponentName . '.blade.php',
        ];

        $sourceFile = null;
        foreach ($sourcePaths as $path) {
            if (file_exists($path)) {
                $sourceFile = $path;
                $output->writeln("<info>Found source component at: {$path}</info>");
                break;
            }
        }

        if (!$sourceFile) {
            $output->writeln("<error>Component not found: {$componentName} (searched in: " . implode(', ', $sourcePaths) . ")</error>");
            return;
        }

        // Create target directory if needed
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            $output->writeln("<info>Created directory: {$targetDir}</info>");
        }

        $targetFile = $targetDir . '/' . $baseComponentName . '.blade.php';
        copy($sourceFile, $targetFile);
        $output->writeln("<info>Copied component {$baseComponentName} to {$targetFile}</info>");
    }
} 