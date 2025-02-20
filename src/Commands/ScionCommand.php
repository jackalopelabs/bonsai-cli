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
    private $sections = [];

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

        // Ensure Heroicons are registered
        $this->ensureHeroiconsRegistration($output);

        // Create component directory
        $componentDir = __DIR__ . '/../../templates/components/' . $templateName;
        if (!is_dir($componentDir)) {
            mkdir($componentDir, 0755, true);
            $output->writeln("<info>Created component directory: {$componentDir}</info>");
        }

        // Read the template file and detect sections
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
        $this->sections = [];
        
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
                
                $this->sections[$sectionKey] = [
                    'component' => $componentName,
                    'data' => $primaryComponent['data'],
                ];

                // Copy the component to the template directory
                $this->copyComponentToTemplate($source, $componentName, $output);
                $output->writeln("<info>Using component {$componentName} for section {$sectionKey}</info>");
            }
        }

        // Now that sections are processed, detect and copy layout
        $this->detectAndCopyLayout($source, $templateName, $output);

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

        // Component name mapping for conventional naming
        $componentNameMap = [
            'site-header' => 'header',
            'site_header' => 'header',
            'site-footer' => 'footer',
            'site_footer' => 'footer',
            'navigation-menu' => 'nav',
            'navigation_menu' => 'nav',
            // Add more mappings as needed
        ];

        // Helper function to clean component names while preserving template namespace
        $cleanComponentName = function($component) use ($templateName, $componentNameMap) {
            // First clean up the component name
            $baseComponent = trim(preg_replace("/^(bonsai::|bonsai\.|{$templateName}\.)/", '', $component));
            
            // Check if we need to map this component name to a more conventional one
            $baseComponent = $componentNameMap[$baseComponent] ?? $baseComponent;
            
            // Always ensure template namespace
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
        $templateName = $this->input->getArgument('templateName');
        $baseComponentName = end($parts);
        
        $projectRoot = dirname(dirname(dirname($sourcePath)));
        
        // Enhanced component path detection with correct order
        $sourcePaths = [
            // First check template-specific components
            "{$projectRoot}/resources/views/bonsai/components/{$templateName}/{$baseComponentName}.blade.php",
            // Then check template root components
            "{$projectRoot}/resources/views/{$templateName}/components/{$baseComponentName}.blade.php",
            // Then check bonsai core components
            "{$projectRoot}/resources/views/bonsai/components/{$baseComponentName}.blade.php",
            // Finally check package defaults
            __DIR__ . "/../../templates/components/{$baseComponentName}.blade.php"
        ];

        $output->writeln("<info>Searching for component in:</info>");
        foreach ($sourcePaths as $path) {
            $output->writeln("- {$path}");
        }

        $sourceFile = null;
        foreach ($sourcePaths as $path) {
            if (file_exists($path)) {
                $sourceFile = $path;
                $output->writeln("<info>✓ Found source component at: {$path}</info>");
                break;
            }
        }

        if (!$sourceFile) {
            $output->writeln("<error>Component not found: {$componentName}</error>");
            return;
        }

        // Create target directories with proper namespacing
        $projectTargetDir = "{$projectRoot}/resources/views/bonsai/components/{$templateName}";
        $packageTargetDir = __DIR__ . "/../../templates/components/{$templateName}";
        
        foreach ([$projectTargetDir, $packageTargetDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $output->writeln("<info>Created directory: {$dir}</info>");
            }
        }

        // Copy and process the component
        $projectTargetPath = "{$projectTargetDir}/{$baseComponentName}.blade.php";
        $packageTargetPath = "{$packageTargetDir}/{$baseComponentName}.blade.php";

        // Read and process the component content
        $componentContent = file_get_contents($sourceFile);
        $componentContent = $this->updateComponentNamespaces($componentContent, $templateName);

        // Write the processed content
        file_put_contents($projectTargetPath, $componentContent);
        file_put_contents($packageTargetPath, $componentContent);

        $output->writeln("<info>✓ Copied and processed component to:</info>");
        $output->writeln("  - {$projectTargetPath}");
        $output->writeln("  - {$packageTargetPath}");

        // Copy any dependencies (icons, etc.)
        $this->copyComponentDependencies($projectRoot, $templateName, $baseComponentName, $output);
    }

    private function copyComponentDependencies(string $projectRoot, string $templateName, string $componentName, OutputInterface $output): void
    {
        // Map of components to their dependencies
        $dependencies = [
            'card' => [
                'icons/flowchart',
                'dynamic-components' => [
                    'image' => true  // Indicates this component uses dynamic image components
                ]
            ],
            'hero' => [
                'icons/github',
                'dynamic-components' => [
                    'dropdownIcon' => true,
                    'buttonLinkIcon' => true,
                    'secondaryIcon' => true
                ]
            ],
            'pricing-box' => [
                'dynamic-components' => [
                    'icon' => true,
                    'iconBtn' => true
                ]
            ]
        ];

        if (!isset($dependencies[$componentName])) {
            return;
        }

        foreach ($dependencies[$componentName] as $key => $dependency) {
            if ($key === 'dynamic-components') {
                // Handle dynamic component configuration
                continue; // Dynamic components don't need to be copied, just configured
            }

            $iconPaths = [
                "{$projectRoot}/resources/views/bonsai/components/{$dependency}.blade.php",
                "{$projectRoot}/resources/views/components/{$dependency}.blade.php",
                __DIR__ . "/../../templates/components/{$dependency}.blade.php"
            ];

            $sourceIcon = null;
            foreach ($iconPaths as $path) {
                if (file_exists($path)) {
                    $sourceIcon = $path;
                    break;
                }
            }

            if ($sourceIcon) {
                // Create icon directories
                $projectIconDir = "{$projectRoot}/resources/views/bonsai/{$templateName}/components/" . dirname($dependency);
                $packageIconDir = __DIR__ . "/../../templates/components/{$templateName}/" . dirname($dependency);

                foreach ([$projectIconDir, $packageIconDir] as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                        $output->writeln("<info>Created dependency directory: {$dir}</info>");
                    }
                }

                // Copy the dependency
                $projectPath = "{$projectIconDir}/" . basename($dependency) . ".blade.php";
                $packagePath = "{$packageIconDir}/" . basename($dependency) . ".blade.php";

                copy($sourceIcon, $projectPath);
                copy($sourceIcon, $packagePath);

                $output->writeln("<info>✓ Copied dependency {$dependency} to:</info>");
                $output->writeln("  - {$projectPath}");
                $output->writeln("  - {$packagePath}");
            } else {
                $output->writeln("<comment>! Dependency {$dependency} not found in any source path</comment>");
            }
        }
    }

    private function updateComponentNamespaces(string $content, string $templateName): string
    {
        // Don't modify dynamic components at all
        $content = preg_replace(
            "/<x-dynamic-component/",
            "<x-dynamic-component",
            $content
        );

        // Don't modify heroicon components
        $content = preg_replace(
            "/<x-heroicon-/",
            "<x-heroicon-",
            $content
        );

        // Update regular component references to use bonsai namespace
        $content = preg_replace(
            "/<x-(?!dynamic-component|heroicon-)([^:\"'\s]+)/",
            "<x-bonsai::{$templateName}.$1",
            $content
        );

        // Update @include directives for bonsai components
        $content = preg_replace(
            "/@include\(['\"]bonsai\.components\./",
            "@include('bonsai.{$templateName}.components.",
            $content
        );

        return $content;
    }

    protected function generateSectionContent($template, $section, $componentType, $data)
    {
        $dataVarName = "{$section}Data";

        $dataLines = [];
        foreach ($data as $key => $value) {
            if ($key === 'iconMappings') {
                // Special handling for icon mappings
                $arrayStr = $this->arrayToPhpString($value, 1);
                $dataLines[] = "    'iconMappings' => {$arrayStr},";
            } else if (is_array($value)) {
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
        $output->writeln("\n<info>Starting layout detection for template: {$templateName}</info>");
        $sourceDir = dirname(dirname(dirname($sourcePath)));
        $content = file_get_contents($sourcePath);

        // Enhanced layout path detection
        $layoutPaths = [
            // Bonsai namespace paths (preferred)
            "{$sourceDir}/resources/views/bonsai/layouts/{$templateName}.blade.php",
            "{$sourceDir}/bonsai/layouts/{$templateName}.blade.php",
            // Template-specific paths
            "{$sourceDir}/resources/views/{$templateName}/layouts/{$templateName}.blade.php",
            "{$sourceDir}/{$templateName}/layouts/{$templateName}.blade.php",
            // Legacy paths
            "{$sourceDir}/layouts/{$templateName}.blade.php",
            "{$sourceDir}/resources/views/layouts/{$templateName}.blade.php"
        ];

        $output->writeln("<info>Searching for layout in following paths:</info>");
        foreach ($layoutPaths as $path) {
            $output->writeln("- {$path}");
        }

        $foundLayoutPath = null;
        foreach ($layoutPaths as $layoutPath) {
            if (file_exists($layoutPath)) {
                $foundLayoutPath = $layoutPath;
                $output->writeln("<info>✓ Found layout at: {$layoutPath}</info>");
                break;
            }
        }

        if ($foundLayoutPath) {
            // Create the target directories with proper namespacing
            $projectLayoutDir = "{$sourceDir}/resources/views/bonsai/layouts";
            $packageLayoutDir = __DIR__ . "/../../templates/layouts";

            foreach ([$projectLayoutDir, $packageLayoutDir] as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                    $output->writeln("<info>Created directory: {$dir}</info>");
                }
            }

            // Copy and update the layout file
            $projectLayoutPath = "{$projectLayoutDir}/{$templateName}.blade.php";
            $packageLayoutPath = "{$packageLayoutDir}/{$templateName}.blade.php";

            // Read and process the layout content
            $layoutContent = file_get_contents($foundLayoutPath);
            
            // Update namespace references
            $layoutContent = $this->updateLayoutNamespaces($layoutContent, $templateName);

            // Write the processed content
            file_put_contents($projectLayoutPath, $layoutContent);
            file_put_contents($packageLayoutPath, $layoutContent);

            $output->writeln("<info>✓ Copied and processed layout to:</info>");
            $output->writeln("  - {$projectLayoutPath}");
            $output->writeln("  - {$packageLayoutPath}");

            // Extract and process layout settings
            $layoutSettings = $this->extractLayoutSettings($layoutContent);
            
            // Copy layout assets with improved asset detection
            $this->copyLayoutAssets($foundLayoutPath, $templateName, $output);

            // Update the config array with the extracted settings
            $config = [
                'sections' => $this->sections,
                'layout' => [
                    'sections' => array_keys($this->sections),
                    'settings' => $layoutSettings
                ]
            ];

            // Save the configuration
            $configDir = __DIR__ . '/../../config/bonsai/templates';
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }
            
            $yamlContent = Yaml::dump($config, 4, 2);
            $targetPath = "{$configDir}/{$templateName}.yml";
            file_put_contents($targetPath, $yamlContent);
            
            $output->writeln("<info>✓ Generated configuration at: {$targetPath}</info>");
        } else {
            $output->writeln("<comment>No custom layout found for {$templateName}, will use default.</comment>");
        }
    }

    private function updateLayoutNamespaces(string $content, string $templateName): string
    {
        // Update @extends directives
        $content = preg_replace(
            "/@extends\(['\"]([^'\"]*)(layouts\.{$templateName})['\"]\\)/",
            "@extends('bonsai.layouts.{$templateName}')",
            $content
        );

        // Update @include directives for sections
        $content = preg_replace(
            "/@include\(['\"]([^'\"]*)(sections\.[^'\"]+)['\"]\\)/",
            "@include('bonsai.{$templateName}.$2')",
            $content
        );

        // Update component references
        $content = preg_replace(
            "/<x-([^:\"'\s]+)/",
            "<x-bonsai::{$templateName}.$1",
            $content
        );

        // Don't modify heroicon components
        $content = preg_replace(
            "/<x-bonsai::{$templateName}\.heroicon-/",
            "<x-heroicon-",
            $content
        );

        return $content;
    }

    private function extractLayoutSettings(string $layoutContent): array
    {
        $settings = [
            'html' => [
                'attributes' => [
                    'language_attributes' => true,
                    'class' => 'dark relative h-screen',
                    'x-data' => "{ darkMode: localStorage.getItem('darkMode') === null ? true : localStorage.getItem('darkMode') === 'true' }",
                    'x-init' => "\$watch('darkMode', val => localStorage.setItem('darkMode', val))",
                    'x-bind:class' => "{ 'dark': darkMode }"
                ]
            ],
            'head' => [
                'meta' => [],
                'includes' => ['bonsai.components.analytics', 'utils.styles']
            ],
            'body' => [
                'attributes' => [
                    'class' => 'transition-colors duration-200 p-0 m-0 bg-transparent'
                ],
                'background' => [
                    'light' => [
                        'image' => 'images/bonsai_hero_03.png',
                        'classes' => 'w-full h-full object-cover object-top opacity-100 block dark:hidden'
                    ],
                    'dark' => [
                        'image' => 'images/bonsai_hero_01.png',
                        'classes' => 'w-full h-full object-cover object-top opacity-100 hidden dark:block'
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
                'includes' => ['utils.scripts']
            ]
        ];

        return $settings;
    }

    private function copyLayoutAssets(string $layoutPath, string $templateName, OutputInterface $output): void
    {
        $content = file_get_contents($layoutPath);
        $output->writeln("<info>Analyzing layout file for assets: {$layoutPath}</info>");
        
        // Enhanced regex patterns for image detection
        $patterns = [
            // Asset helper pattern
            "/asset\(['\"]([^'\"]+)['\"]\)/",
            // Direct image paths
            "/['\"]([^'\"]*\.(?:png|jpg|jpeg|gif|svg|webp))['\"]/"
        ];

        $assetPaths = [];
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            if (!empty($matches[1])) {
                $assetPaths = array_merge($assetPaths, $matches[1]);
            }
        }

        // Remove duplicates and filter out external URLs
        $assetPaths = array_unique(array_filter($assetPaths, function($path) {
            return !preg_match('/^(https?:)?\/\//', $path);
        }));

        if (!empty($assetPaths)) {
            $output->writeln("<info>Found " . count($assetPaths) . " asset references</info>");
            
            // Create assets directory in the package
            $packageAssetsDir = __DIR__ . "/../../templates/assets/{$templateName}";
            if (!is_dir($packageAssetsDir)) {
                mkdir($packageAssetsDir, 0755, true);
                $output->writeln("<info>Created assets directory: {$packageAssetsDir}</info>");
            }

            // Get the correct project root from the source option
            $sourcePath = $this->input->getOption('source');
            $projectRoot = dirname(dirname(dirname($sourcePath)));
            
            // Multiple source paths to look for assets
            $sourcePaths = [
                "{$projectRoot}/resources/images",                // Primary: Resources images
                "{$projectRoot}/public/images",                   // Public images dir
                "{$projectRoot}/public",                         // Public root
                "{$projectRoot}/assets/images",                  // Assets images
                dirname($layoutPath) . "/images",                // Layout-relative images
                "{$projectRoot}/images",                        // Root images
                // Add the correct path that matches the user's structure
                "/Users/masonlawlor/Sites/bonsai.so/resources/images"  // Explicit path
            ];

            foreach ($assetPaths as $assetPath) {
                $output->writeln("<info>Looking for asset: {$assetPath}</info>");
                $found = false;

                // Clean the asset path
                $assetPath = ltrim($assetPath, '/');
                $filename = basename($assetPath);
                
                foreach ($sourcePaths as $sourcePath) {
                    // Try both with full path and just filename
                    $paths = [
                        $sourcePath . '/' . $assetPath,
                        $sourcePath . '/' . $filename
                    ];
                    
                    foreach ($paths as $fullSourcePath) {
                        $output->writeln("<comment>Checking: {$fullSourcePath}</comment>");
                        
                        if (file_exists($fullSourcePath)) {
                            $targetAssetPath = "{$packageAssetsDir}/" . $filename;
                            try {
                                if (copy($fullSourcePath, $targetAssetPath)) {
                                    $output->writeln("<info>✓ Successfully copied: {$filename}</info>");
                                    $found = true;
                                    break 2;
                                } else {
                                    $output->writeln("<e>Failed to copy: {$filename}</e>");
                                }
                            } catch (\Exception $e) {
                                $output->writeln("<e>Error copying {$filename}: {$e->getMessage()}</e>");
                            }
                        }
                    }
                }

                if (!$found) {
                    $output->writeln("<comment>! Asset not found in any source path: {$filename}</comment>");
                }
            }
        } else {
            $output->writeln("<comment>No assets found in layout file</comment>");
        }

        // Handle widget-specific image paths
        $this->copyWidgetAssets($layoutPath, $templateName, $output);
    }

    private function copyWidgetAssets(string $layoutPath, string $templateName, OutputInterface $output): void
    {
        $projectRoot = dirname(dirname(dirname($layoutPath)));
        $widgetPaths = [
            "{$projectRoot}/resources/views/bonsai/components/widget",
            "{$projectRoot}/resources/views/components/widget",
            "{$projectRoot}/views/components/widget"
        ];

        foreach ($widgetPaths as $widgetPath) {
            if (is_dir($widgetPath)) {
                $output->writeln("<info>Checking widget directory: {$widgetPath}</info>");
                
                $files = glob("{$widgetPath}/*.blade.php");
                foreach ($files as $file) {
                    $content = file_get_contents($file);
                    if (preg_match_all("/['\"]([^'\"]*\.(?:png|jpg|jpeg|gif|svg|webp))['\"]/",$content, $matches)) {
                        foreach ($matches[1] as $imagePath) {
                            $this->copyAssetFromPath($imagePath, $templateName, $output);
                        }
                    }
                }
            }
        }
    }

    private function copyAssetFromPath(string $assetPath, string $templateName, OutputInterface $output): void
    {
        $packageAssetsDir = __DIR__ . "/../../templates/assets/{$templateName}";
        
        // Ensure directory exists
        if (!is_dir($packageAssetsDir)) {
            mkdir($packageAssetsDir, 0755, true);
        }

        $targetPath = "{$packageAssetsDir}/" . basename($assetPath);
        if (file_exists($assetPath)) {
            try {
                if (copy($assetPath, $targetPath)) {
                    $output->writeln("<info>✓ Copied widget asset: {$assetPath}</info>");
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Failed to copy widget asset {$assetPath}: {$e->getMessage()}</error>");
            }
        } else {
            $output->writeln("<comment>Widget asset not found: {$assetPath}</comment>");
        }
    }

    protected function generateTemplateContent($template, $layout, $config)
    {
        $sections = $config['sections'] ?? [];
        $sectionIncludes = array_map(function($section) use ($template) {
            return "@include('bonsai.sections.{$section}')";
        }, $sections);

        return <<<BLADE
{{-- 
    Template Name: {{ \$config['name'] ?? ucfirst(\$template) }}
--}}
@extends('bonsai.layouts.{$layout}')

@section('content')
{$this->indent(implode("\n", $sectionIncludes), 4)}
@endsection
BLADE;
    }

    protected function generateLayouts($layouts)
    {
        $template = $this->argument('template');
        $config = $this->loadConfig($this->getConfigPath($template));
        $themeSettings = $config['theme'] ?? [
            'body' => ['class' => 'bg-gray-100']
        ];

        foreach ($layouts as $layout => $layoutConfig) {
            $layoutPath = resource_path("views/bonsai/layouts/{$layout}.blade.php");
            if (!$this->files->exists(dirname($layoutPath))) {
                $this->files->makeDirectory(dirname($layoutPath), 0755, true);
            }

            $layoutContent = <<<BLADE
<!doctype html>
<html @php(language_attributes()) class="dark relative h-screen" x-data="{ darkMode: localStorage.getItem('darkMode') === null ? true : localStorage.getItem('darkMode') === 'true' }" x-init="\$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
    <!-- Hero Background Images -->
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('images/bonsai_hero_03.png') }}" 
             alt="Background Light" 
             class="w-full h-full object-cover object-top opacity-100 block dark:hidden"
        />
        <img src="{{ asset('images/bonsai_hero_01.png') }}" 
             alt="Background Dark" 
             class="w-full h-full object-cover object-top opacity-100 hidden dark:block"
        />
    </div>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @include('bonsai.components.analytics')
        @include('utils.styles')
    </head>
    <body @php(body_class('transition-colors duration-200 p-0 m-0 bg-transparent'))>
        @php(wp_body_open())
        <div id="app" class="relative z-10">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>
            @include('bonsai.sections.site_header')
            <main id="main" class="max-w-5xl mx-auto">
                <div class="{{ \$containerInnerClasses }}">
                    @yield('content')
                </div>
            </main>
            @include('bonsai.components.footer')
        </div>
        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html>
BLADE;

            $this->files->put($layoutPath, $layoutContent);
        }
    }

    protected function getComponentSearchPaths(string $projectRoot, string $component, string $templateName): array
    {
        return [
            "{$projectRoot}/resources/views/bonsai/components/{$templateName}/{$component}.blade.php",
            "{$projectRoot}/resources/views/bonsai/components/{$component}.blade.php",
            "{$projectRoot}/resources/views/cypress/components/{$component}.blade.php",
            "{$projectRoot}/resources/views/components/{$component}.blade.php",
            "{$this->getTemplatesPath()}/components/{$component}.blade.php",
        ];
    }

    protected function getAssetSearchPaths(string $projectRoot, string $assetPath): array
    {
        $assetName = basename($assetPath);
        $assetDir = dirname($assetPath);
        
        return [
            // Direct project paths
            "{$projectRoot}/resources/images/{$assetName}",
            "{$projectRoot}/resources/images/{$assetPath}",
            // Public paths
            "{$projectRoot}/public/images/{$assetName}",
            "{$projectRoot}/public/images/{$assetPath}",
            "{$projectRoot}/public/{$assetName}",
            // Asset paths
            "{$projectRoot}/assets/images/{$assetName}",
            "{$projectRoot}/assets/images/{$assetPath}",
            // Root paths
            "{$projectRoot}/images/{$assetName}",
            "{$projectRoot}/images/{$assetPath}"
        ];
    }

    protected function copyComponent(string $component, string $templateName, OutputInterface $output): bool
    {
        $projectRoot = $this->getProjectRoot();
        $searchPaths = $this->getComponentSearchPaths($projectRoot, $component, $templateName);
        
        $output->writeln("Starting component copy process for: {$component}");
        $output->writeln("Searching for component in:");
        foreach ($searchPaths as $path) {
            $output->writeln("- {$path}");
        }

        $sourceComponentPath = null;
        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                $sourceComponentPath = $path;
                break;
            }
        }

        if (!$sourceComponentPath) {
            $output->writeln("<error>Component not found in any search path: {$component}</error>");
            return false;
        }

        $output->writeln("✓ Found source component at: {$sourceComponentPath}");

        // Copy to project components directory
        $destComponentPath = "{$projectRoot}/resources/views/bonsai/components/{$templateName}/{$component}.blade.php";
        $this->ensureDirectoryExists(dirname($destComponentPath));
        copy($sourceComponentPath, $destComponentPath);

        // Copy to templates directory
        $templatesComponentPath = "{$this->getTemplatesPath()}/components/{$templateName}/{$component}.blade.php";
        $this->ensureDirectoryExists(dirname($templatesComponentPath));
        copy($sourceComponentPath, $templatesComponentPath);

        $output->writeln("✓ Copied and processed component to:");
        $output->writeln("  - {$destComponentPath}");
        $output->writeln("  - {$templatesComponentPath}");

        return true;
    }

    protected function copyAsset(string $assetPath, OutputInterface $output): bool
    {
        $projectRoot = $this->getProjectRoot();
        $searchPaths = $this->getAssetSearchPaths($projectRoot, $assetPath);
        
        $output->writeln("Looking for asset: {$assetPath}");
        foreach ($searchPaths as $path) {
            $output->writeln("Checking: {$path}");
            if (file_exists($path)) {
                // Create target directory if it doesn't exist
                $destDir = dirname("{$this->getTemplatesPath()}/assets/{$assetPath}");
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
                // Copy the asset
                $destPath = "{$this->getTemplatesPath()}/assets/{$assetPath}";
                if (copy($path, $destPath)) {
                    $output->writeln("✓ Copied asset to: {$destPath}");
                    return true;
                } else {
                    $output->writeln("! Failed to copy asset to: {$destPath}");
                }
            }
        }

        $output->writeln("! Asset not found in any source path: " . basename($assetPath));
        return false;
    }

    private function ensureHeroiconsRegistration(OutputInterface $output)
    {
        $output->writeln("<info>Ensuring Heroicons registration...</info>");
        
        // Check if ViewServiceProvider exists
        $providerPath = $this->getProjectRoot() . '/app/Providers/ViewServiceProvider.php';
        if (!file_exists($providerPath)) {
            $output->writeln("<comment>ViewServiceProvider not found. Creating...</comment>");
            $this->createViewServiceProvider();
        }

        $content = file_get_contents($providerPath);
        
        // Check if Heroicons are already registered
        if (strpos($content, 'heroicon-') === false) {
            $output->writeln("<info>Registering Heroicons in ViewServiceProvider...</info>");
            
            // Add Heroicons registration
            $registrationCode = "\n        // Register Heroicons\n";
            $registrationCode .= "        \$styles = ['o' => 'outline', 's' => 'solid', 'm' => 'mini'];\n";
            $registrationCode .= "        foreach (\$styles as \$prefix => \$style) {\n";
            $registrationCode .= "            \$path = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/' . \$style);\n";
            $registrationCode .= "            if (is_dir(\$path)) {\n";
            $registrationCode .= "                foreach (glob(\$path . '/*.svg') as \$file) {\n";
            $registrationCode .= "                    \$baseFilename = basename(\$file, '.svg');\n";
            $registrationCode .= "                    \$componentName = \"heroicon-{\$prefix}-{\$baseFilename}\";\n";
            $registrationCode .= "                    Blade::component(\"heroicons::{\$style}.{\$baseFilename}\", \$componentName);\n";
            $registrationCode .= "                }\n";
            $registrationCode .= "            }\n";
            $registrationCode .= "        }\n";

            // Insert the registration code after the boot method opening
            if (preg_match('/public function boot\(\)\s*{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1] + strlen($matches[0][0]);
                $content = substr_replace($content, $registrationCode, $position, 0);
                file_put_contents($providerPath, $content);
                $output->writeln("<info>✓ Added Heroicons registration to ViewServiceProvider</info>");
            }
        } else {
            $output->writeln("<info>✓ Heroicons already registered in ViewServiceProvider</info>");
        }
    }

    private function createViewServiceProvider()
    {
        $providerPath = $this->getProjectRoot() . '/app/Providers/ViewServiceProvider.php';
        $dir = dirname($providerPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Component registrations will be added here
    }
}
PHP;

        file_put_contents($providerPath, $content);

        // Add to config/app.php providers array if it exists
        $configPath = $this->getProjectRoot() . '/config/app.php';
        if (file_exists($configPath)) {
            $config = file_get_contents($configPath);
            if (strpos($config, 'App\\Providers\\ViewServiceProvider::class') === false) {
                $config = preg_replace(
                    '/(\'providers\' => \[\s+)/s',
                    "$1        App\\Providers\\ViewServiceProvider::class,\n",
                    $config
                );
                file_put_contents($configPath, $config);
            }
        }
    }

    private function getProjectRoot(): string
    {
        // Get the source path from the input
        $sourcePath = $this->input->getOption('source');
        
        // If source path is provided, use it to determine project root
        if ($sourcePath) {
            // Clean up the path to remove any duplicate 'resources/views'
            $path = str_replace('/resources/views/resources/views/', '/resources/views/', $sourcePath);
            
            // Navigate up from the template file to find the project root
            // Assuming standard structure: project_root/resources/views/...
            $parts = explode('/resources/views/', $path);
            if (count($parts) > 1) {
                return $parts[0];
            }
            
            // Fallback to standard directory traversal
            return dirname(dirname(dirname($path)));
        }
        
        // Fallback to current working directory
        return getcwd();
    }

    protected function copyTemplateComponent($componentName, OutputInterface $output)
    {
        $output->writeln("\n🔍 Attempting to copy template component: {$componentName}");
        
        // First check for Heroicon dependencies
        $this->checkAndRegisterHeroiconDependencies($componentName, $output);
        
        $template = $this->input->getArgument('templateName');
        $output->writeln("Template: {$template}");
        
        $possiblePaths = [
            // Primary: Template-specific components in bonsai namespace
            base_path("resources/views/bonsai/components/{$template}/{$componentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$template}/{$componentName}.blade.php",
            // Secondary: Legacy template paths
            base_path("templates/{$template}/components/{$componentName}.blade.php"),
            base_path("resources/views/{$template}/components/{$componentName}.blade.php")
        ];

        $output->writeln("Checking possible source paths:");
        foreach ($possiblePaths as $path) {
            $output->writeln("  - {$path}");
            if (file_exists($path)) {
                $output->writeln("✓ Found component at: {$path}");
                
                // Create bonsai template-specific component directory
                $targetDir = resource_path("views/bonsai/components/{$template}");
                $output->writeln("Creating target directory: {$targetDir}");
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                    $output->writeln("✓ Created directory");
                }
                
                $targetPath = "{$targetDir}/{$componentName}.blade.php";
                $output->writeln("Copying to: {$targetPath}");
                
                try {
                    // Read the component content
                    $content = file_get_contents($path);
                    
                    // Check for Heroicon usage and ensure they're registered
                    if (preg_match_all('/<x-heroicon-[osm]-([^"\s]+)/', $content, $matches)) {
                        $output->writeln("Found Heroicon dependencies:");
                        foreach ($matches[1] as $iconName) {
                            $output->writeln("  - {$iconName}");
                        }
                    }
                    
                    // Write the component
                    file_put_contents($targetPath, $content);
                    $output->writeln("✓ Successfully copied component");
                    
                    return true;
                } catch (\Exception $e) {
                    $output->writeln("<error>Failed to copy component: " . $e->getMessage() . "</error>");
                    return false;
                }
            }
        }

        $output->writeln("❌ Component not found in any source path");
        return false;
    }

    private function checkAndRegisterHeroiconDependencies($componentName, OutputInterface $output)
    {
        // Known components that use Heroicons
        $heroiconDependencies = [
            'hero' => [
                'chevron-down',
                'shopping-cart',
                'chevron-right'
            ],
            'header' => [
                'menu',
                'x-mark'
            ],
            // Add more components and their Heroicon dependencies as needed
        ];

        if (isset($heroiconDependencies[$componentName])) {
            $output->writeln("<info>Component {$componentName} has Heroicon dependencies</info>");
            
            // Ensure ViewServiceProvider exists and has Heroicon registration
            $this->ensureHeroiconsRegistration($output);
            
            // Log the specific icons being used
            foreach ($heroiconDependencies[$componentName] as $icon) {
                $output->writeln("  - Registered dependency: {$icon}");
            }
        }
    }
} 