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
    private $input;

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
        $this->input = $input;  // Store input for use in other methods
        
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

        // Read the template file and detect layout
        $content = file_get_contents($source);
        $this->detectAndCopyLayout($source, $templateName, $output);

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
            $sectionKey = end($parts);  // Automatically use the default key
            $output->writeln("<info>Using section key: {$sectionKey}</info>");

            // Analyze section file to find component usage
            $componentInfo = $this->analyzeSectionComponents($sectionPath, $output);
            if (empty($componentInfo)) {
                $output->writeln("<error>No components found in section: {$sectionPath}</error>");
                continue;
            }

            // Get the primary component and its data
            $primaryComponent = $this->getPrimaryComponent($componentInfo);
            if ($primaryComponent) {
                $componentName = $primaryComponent['name'];
                
                $sections[$sectionKey] = [
                    'component' => $componentName,
                    'data' => $primaryComponent['data'],
                ];

                // Copy the component to the template directory
                $this->copyComponentToTemplate($source, $componentName, $output);
                $output->writeln("<info>Using component {$componentName} for section {$sectionKey}</info>");
            }
        }

        // Use all section keys in order for layout
        $layoutSections = array_keys($sections);

        // Build the configuration array
        $config = [
            'sections' => $sections,
            'layout'   => [
                'sections' => $layoutSections,
                'settings' => [
                    'html' => [
                        'attributes' => [
                            'language_attributes' => true,
                            'class' => 'no-js',
                            'x-data' => "{ darkMode: localStorage.getItem('darkMode') === 'true' }",
                            'x-init' => "darkMode = localStorage.getItem('darkMode') === 'true'; $watch('darkMode', value => localStorage.setItem('darkMode', value))",
                            'x-bind:class' => "{ 'dark': darkMode }"
                        ]
                    ],
                    'head' => [
                        'meta' => [],
                        'includes' => []
                    ],
                    'body' => [
                        'attributes' => [
                            'class' => 'font-sans antialiased bg-gradient-to-br from-indigo-50 to-blue-100 dark:from-midnight-950 dark:to-midnight-900 min-h-screen transition-colors duration-300'
                        ],
                        'background' => [
                            'light' => [
                                'image' => 'images/bonsai_hero_03.png',
                                'classes' => 'absolute inset-0 w-full h-full object-cover opacity-50 dark:hidden'
                            ],
                            'dark' => [
                                'image' => 'images/bonsai_hero_01.png',
                                'classes' => 'absolute inset-0 w-full h-full object-cover opacity-50 hidden dark:block'
                            ]
                        ],
                        'structure' => [
                            'app' => [
                                'class' => 'relative z-10',
                                'skip_link' => [
                                    'text' => 'Skip to content',
                                    'target' => '#main'
                                ],
                                'header' => [
                                    'include' => 'bonsai.sections.site_header'
                                ],
                                'main' => [
                                    'id' => 'main',
                                    'class' => 'max-w-5xl mx-auto',
                                    'content_wrapper' => [
                                        'class' => '{{ $containerInnerClasses }}'
                                    ]
                                ],
                                'footer' => [
                                    'include' => 'bonsai.components.footer'
                                ]
                            ]
                        ],
                        'includes' => []
                    ]
                ]
            ]
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
        $output->writeln("<info>Analyzing section: {$sectionPath}</info>");
        
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
        $output->writeln("<info>Section content:</info>");
        $output->writeln($content);
        
        $components = [];

        // Extract template name from include path for template-specific sections
        if (preg_match('|bonsai\.sections\.([^\.]+)\.|', $sectionPath, $matches)) {
            $templateName = $matches[1];  // e.g., 'cypress' from bonsai.sections.cypress.home_hero
            $output->writeln("<info>Found template name from section path: {$templateName}</info>");
        } else {
            // Fallback to extracting from file path
            preg_match('|/([^/]+)/sections/|', $sectionPath, $matches);
            $templateName = $matches[1] ?? null;
            
            // If template name is 'bonsai', look for nested template name
            if ($templateName === 'bonsai') {
                preg_match('|/bonsai/sections/([^/]+)/|', $sectionPath, $matches);
                $templateName = $matches[1] ?? null;
            }
            $output->writeln("<info>Found template name from file path: {$templateName}</info>");
        }

        // Helper function to clean component names while preserving template namespace
        $cleanComponentName = function($component) use ($templateName) {
            // Always ensure template namespace for components
            $baseComponent = trim(preg_replace("/^(bonsai::|bonsai\.|{$templateName}\.)/", '', $component));
            return $templateName . '.' . $baseComponent;
        };

        // Look for x-bonsai:: components first (highest priority)
        preg_match_all("/<x-bonsai::([^\\s>]+)(?:\\s+[^>]*)?>/", $content, $matches);
        if (!empty($matches[1])) {
            $output->writeln("<info>Found x-bonsai components in content:</info>");
            foreach ($matches[1] as $component) {
                $output->writeln(" - Raw component: {$component}");
                if (!$this->shouldSkipComponent($component)) {
                    $componentName = $cleanComponentName($component);
                    $components[$componentName] = [
                        'type' => 'x-bonsai',
                        'name' => $componentName,
                        'data' => $this->extractComponentData($content),
                        'priority' => 1,
                    ];
                    $output->writeln("<info>Added component: {$componentName}</info>");
                } else {
                    $output->writeln("<comment>Skipping component: {$component}</comment>");
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

    protected function copyComponentToTemplate(string $sourcePath, string $componentName, OutputInterface $output): void
    {
        $output->writeln("\n<info>Starting component copy process for: {$componentName}</info>");
        
        // Handle nested components (e.g., cypress.hero)
        $parts = explode('.', $componentName);
        $output->writeln("<info>Component parts: " . implode(', ', $parts) . "</info>");
        
        // Get the template name from the command argument
        $templateName = $this->input->getArgument('templateName');
        $baseComponentName = end($parts); // Get the base name (e.g., 'hero' from 'cypress.hero')
        $output->writeln("<info>Template name: {$templateName}</info>");
        $output->writeln("<info>Base component name: {$baseComponentName}</info>");
        
        // Get the source project root from the template path provided to the command
        $sourceProjectRoot = dirname(dirname(dirname($sourcePath))); // Get to the project root (3 levels up from template file)
        
        // Debug output
        $output->writeln("<info>Source file path: {$sourcePath}</info>");
        $output->writeln("<info>Source project root: {$sourceProjectRoot}</info>");
        
        // Prioritize bonsai namespace paths
        $sourcePaths = [
            // Primary: Template-specific components in bonsai namespace
            "{$sourceProjectRoot}/bonsai/components/{$templateName}/{$baseComponentName}.blade.php",
            // Secondary: Shared components in bonsai namespace
            "{$sourceProjectRoot}/bonsai/components/{$baseComponentName}.blade.php",
            // Fallback paths
            "{$sourceProjectRoot}/{$templateName}/components/{$baseComponentName}.blade.php",
            "{$sourceProjectRoot}/components/{$templateName}/{$baseComponentName}.blade.php",
            // Last resort: default templates
            __DIR__ . "/../../templates/components/{$baseComponentName}.blade.php",
        ];

        $output->writeln("<info>Searching in the following paths:</info>");
        foreach ($sourcePaths as $index => $path) {
            $output->writeln("<info>" . ($index + 1) . ". {$path}</info>");
        }

        $sourceFile = null;
        foreach ($sourcePaths as $path) {
            $output->writeln("\n<info>Checking path: {$path}</info>");
            if (file_exists($path)) {
                $sourceFile = $path;
                $output->writeln("<info>✓ Found source component at: {$path}</info>");
                $output->writeln("<info>Component content:</info>");
                $output->writeln(file_get_contents($path));
                break;
            } else {
                $output->writeln("<comment>✗ Not found at: {$path}</comment>");
            }
        }

        if (!$sourceFile) {
            $output->writeln("<error>Component not found: {$componentName} (searched in: " . implode(', ', $sourcePaths) . ")</error>");
            return;
        }

        // Create both the project's resources directory and the package's templates directory
        $projectTargetDir = "{$sourceProjectRoot}/bonsai/components/{$templateName}";
        $packageTargetDir = __DIR__ . "/../../templates/components/{$templateName}";
        
        // Create directories if they don't exist
        foreach ([$projectTargetDir, $packageTargetDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Copy to both locations
        $projectTargetPath = "{$projectTargetDir}/{$baseComponentName}.blade.php";
        $packageTargetPath = "{$packageTargetDir}/{$baseComponentName}.blade.php";
        
        copy($sourceFile, $projectTargetPath);
        copy($sourceFile, $packageTargetPath);
        
        $output->writeln("<info>Copied component to: {$projectTargetPath}</info>");
        $output->writeln("<info>Final component content:</info>");
        $output->writeln(file_get_contents($projectTargetPath));
    }

    protected function generateSectionContent($template, $section, $componentType, $data)
    {
        $dataVarName = "{$section}Data";

        $dataLines = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $arrayStr = $this->arrayToPhpString($value, 1);
                $dataLines[] = "    '{$key}' => {$arrayStr},";
            } else {
                $dataLines[] = "    '{$key}' => " . var_export($value, true) . ",";
            }
        }

        // Build the section content with proper component reference
        return <<<BLADE
@props([
    'class' => ''
])

@php
\${$dataVarName} = [
{$this->indent(implode("\n", $dataLines), 0)}
];
@endphp

<div class="{{ \$class }}">
    <x-bonsai::{$template}.{$componentType} :data="\${$dataVarName}" />
</div>
BLADE;
    }

    private function indent($string, $level)
    {
        $indent = str_repeat('    ', $level);
        return $indent . str_replace("\n", "\n" . $indent, $string);
    }

    private function arrayToPhpString($array, $level)
    {
        $indent = str_repeat('    ', $level);
        $lines = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $lines[] = "{$indent}'{$key}' => " . $this->arrayToPhpString($value, $level + 1);
            } else {
                $lines[] = "{$indent}'{$key}' => " . var_export($value, true);
            }
        }
        return "[\n" . implode(",\n", $lines) . "\n" . $indent . "]";
    }

    private function detectAndCopyLayout(string $sourcePath, string $templateName, OutputInterface $output): void
    {
        $sourceDir = dirname(dirname(dirname($sourcePath)));
        $content = file_get_contents($sourcePath);

        // Try to find the layout file
        $layoutPaths = [
            "{$sourceDir}/bonsai/layouts/{$templateName}.blade.php",
            "{$sourceDir}/{$templateName}/layouts/{$templateName}.blade.php",
            "{$sourceDir}/layouts/{$templateName}.blade.php",
        ];

        $foundLayoutPath = null;
        foreach ($layoutPaths as $layoutPath) {
            if (file_exists($layoutPath)) {
                $foundLayoutPath = $layoutPath;
                break;
            }
        }

        if ($foundLayoutPath) {
            // Create the target directories
            $projectLayoutDir = dirname(dirname(dirname($sourcePath))) . "/bonsai/layouts";
            $packageLayoutDir = __DIR__ . "/../../templates/layouts";

            foreach ([$projectLayoutDir, $packageLayoutDir] as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            // Copy the layout file to both locations
            $projectLayoutPath = "{$projectLayoutDir}/{$templateName}.blade.php";
            $packageLayoutPath = "{$packageLayoutDir}/{$templateName}.blade.php";

            copy($foundLayoutPath, $projectLayoutPath);
            copy($foundLayoutPath, $packageLayoutPath);

            $output->writeln("<info>Copied layout from: {$foundLayoutPath}</info>");
            $output->writeln("<info>To project: {$projectLayoutPath}</info>");
            $output->writeln("<info>To package: {$packageLayoutPath}</info>");

            // Also copy any required assets
            $this->copyLayoutAssets($foundLayoutPath, $templateName, $output);
        } else {
            $output->writeln("<comment>No custom layout found for {$templateName}, will use default.</comment>");
        }
    }

    private function copyLayoutAssets(string $layoutPath, string $templateName, OutputInterface $output): void
    {
        $content = file_get_contents($layoutPath);
        
        // Extract asset paths from the layout file
        preg_match_all("/asset\(['\"]([^'\"]+)['\"]\)/", $content, $matches);
        $assetPaths = $matches[1] ?? [];

        if (!empty($assetPaths)) {
            // Create assets directory in the package
            $packageAssetsDir = __DIR__ . "/../../templates/assets/{$templateName}";
            if (!is_dir($packageAssetsDir)) {
                mkdir($packageAssetsDir, 0755, true);
            }

            foreach ($assetPaths as $assetPath) {
                // Try to find the asset in the project's public directory
                $sourceAssetPath = dirname(dirname(dirname($layoutPath))) . "/public/{$assetPath}";
                if (file_exists($sourceAssetPath)) {
                    $targetAssetPath = "{$packageAssetsDir}/" . basename($assetPath);
                    copy($sourceAssetPath, $targetAssetPath);
                    $output->writeln("<info>Copied asset: {$assetPath} to package templates</info>");
                } else {
                    $output->writeln("<comment>Asset not found: {$assetPath}</comment>");
                }
            }
        }
    }
} 