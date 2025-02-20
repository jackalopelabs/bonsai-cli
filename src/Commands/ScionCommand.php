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
    protected static $defaultName = 'scion';
    protected InputInterface $input;
    private $sections = [];

    protected function configure(): void
    {
        $this
            ->setDescription('Extract sections and components from a template')
            ->addArgument('template', InputArgument::REQUIRED, 'The template name')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'The source template file path')
            ->addOption('project-root', null, InputOption::VALUE_OPTIONAL, 'The project root directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;  // Store input for use in other methods
        $sourcePath = $input->getOption('source');
        $templateName = $input->getArgument('template');

        $output->writeln("\nStarting extraction for template: $templateName");

        // Ensure Heroicons are registered
        $this->ensureHeroiconsRegistration($output);

        // Find all includes in the source file
        $content = file_get_contents($sourcePath);
        preg_match_all('/\@include\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches);

        $output->writeln("Found the following includes:");
        foreach ($matches[1] as $include) {
            $output->writeln(" - $include");
        }

        // Process each section
        foreach ($matches[1] as $include) {
            $sectionKey = basename(str_replace('.', '/', $include));
            $output->writeln("Using section key: $sectionKey");

            // Convert include path to file path
            $sectionPath = str_replace('.', '/', $include);
            $sectionPath = $this->getResourcePath("views/$sectionPath.blade.php");

            $output->writeln("Analyzing section: $sectionPath");
            if (file_exists($sectionPath)) {
                $output->writeln("Section content:");
                $sectionContent = file_get_contents($sectionPath);
                $output->writeln($sectionContent);

                // Find x-bonsai components
                preg_match_all('/<x-bonsai::([^:\s>]+)/', $sectionContent, $componentMatches);
                if (!empty($componentMatches[1])) {
                    $output->writeln("Found x-bonsai components in content:");
                    foreach ($componentMatches[1] as $component) {
                        $output->writeln(" - Raw component: $component");
                        $this->copyComponentToTemplate($sourcePath, $component, $output);
                    }
                }
            }
        }

        // Copy layout file
        $this->detectAndCopyLayout($sourcePath, $templateName, $output);

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
        $templateName = $this->input->getArgument('template');
        $output->writeln("\n<info>🔍 Analyzing source component: $componentName from template $templateName</info>");
        
        // Handle namespaced components (e.g., cypress.hero)
        $componentParts = explode('.', $componentName);
        $componentBaseName = end($componentParts);
        $componentNamespace = count($componentParts) > 1 ? $componentParts[0] : '';
        
        // Define source project paths to check
        $sourceProjectPaths = [
            // Check in template-specific directory first
            $this->getResourcePath("views/bonsai/components/{$templateName}/{$componentBaseName}.blade.php"),
            // Then check in namespace directory if it exists
            $this->getResourcePath("views/bonsai/components/{$componentNamespace}/{$componentBaseName}.blade.php"),
            // Then check in root components directory
            $this->getResourcePath("views/bonsai/components/{$componentBaseName}.blade.php"),
            // Finally check in parent directories
            dirname($sourcePath) . "/../components/{$componentBaseName}.blade.php",
            dirname($sourcePath) . "/../../components/{$componentBaseName}.blade.php"
        ];
        
        $output->writeln("<info>Checking source project paths:</info>");
        foreach ($sourceProjectPaths as $path) {
            $output->writeln("  - $path");
            if (file_exists($path)) {
                $output->writeln("<info>✓ Found component at: $path</info>");
                
                // Create target directory
                $targetDir = $this->getOutputPath("templates/components/{$templateName}");
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                // Copy the component
                $targetPath = $targetDir . "/{$componentBaseName}.blade.php";
                copy($path, $targetPath);
                $output->writeln("<info>✓ Copied component to: $targetPath</info>");
                return;
            }
        }
        
        // Check package default paths as fallback
        $output->writeln("\n<info>Checking package default paths:</info>");
        $packagePaths = [
            $this->getOutputPath("templates/components/{$templateName}/{$componentBaseName}.blade.php"),
            $this->getOutputPath("templates/components/{$componentBaseName}.blade.php")
        ];
        
        foreach ($packagePaths as $path) {
            $output->writeln("  - $path");
            if (file_exists($path)) {
                $output->writeln("<info>✓ Found default component at: $path</info>");
                return;
            }
        }
        
        $output->writeln("<error>❌ Component not found in any location</error>");
    }

    private function getComponentSearchPaths(string $projectRoot, string $component, string $templateName): array
    {
        return [
            // Source project paths
            "{$projectRoot}/resources/views/bonsai/components/{$templateName}/{$component}.blade.php",
            "{$projectRoot}/resources/views/bonsai/components/{$component}.blade.php",
            // Package paths
            __DIR__ . "/../../templates/components/{$templateName}/{$component}.blade.php",
            __DIR__ . "/../../templates/components/{$component}.blade.php",
        ];
    }

    protected function ensureHeroiconsRegistration(OutputInterface $output): void
    {
        $output->writeln("<info>Ensuring Heroicons registration...</info>");
        
        // Check if ViewServiceProvider exists
        $providerPath = $this->getProjectRoot() . '/app/Providers/ViewServiceProvider.php';
        if (!file_exists($providerPath)) {
            $output->writeln("<comment>ViewServiceProvider not found. Creating...</comment>");
            $this->createViewServiceProvider();
        }
        
        // Read the current content
        $content = file_get_contents($providerPath);
        
        // Check if Heroicons are already registered
        if (strpos($content, 'heroicon-') === false) {
            $output->writeln("<info>Registering Heroicons in ViewServiceProvider...</info>");
            
            // Add Heroicons registration
            $registrationCode = "\n        // Register Heroicons\n";
            $registrationCode .= "        foreach (glob(resource_path('views/components/icons/*.blade.php')) as \$icon) {\n";
            $registrationCode .= "            \$iconName = basename(\$icon, '.blade.php');\n";
            $registrationCode .= "            Blade::component('components.icons.' . \$iconName, \$iconName);\n";
            $registrationCode .= "        }\n";
            
            // Find the position to insert the registration code
            $position = strpos($content, 'public function boot');
            if ($position !== false) {
                $position = strpos($content, '{', $position) + 1;
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

    protected function getProjectRoot(): string
    {
        $projectRoot = $this->input->getOption('project-root');
        if (!$projectRoot) {
            $projectRoot = getcwd();
        }
        return rtrim($projectRoot, '/');
    }

    protected function getTargetPath(string $path): string
    {
        return $this->getProjectRoot() . '/' . ltrim($path, '/');
    }

    protected function getResourcePath(string $path): string
    {
        $projectRoot = $this->getProjectRoot();
        return $projectRoot . '/resources/' . ltrim($path, '/');
    }

    protected function getOutputPath(string $path): string
    {
        return getcwd() . '/' . ltrim($path, '/');
    }

    protected function copyTemplateComponent($componentName, OutputInterface $output)
    {
        $output->writeln("\n🔍 Attempting to copy template component: {$componentName}");
        
        // First check for Heroicon dependencies
        $this->checkAndRegisterHeroiconDependencies($componentName, $output);
        
        $template = $this->input->getArgument('template');
        $output->writeln("Template: {$template}");
        
        $possiblePaths = [
            // Primary: Template-specific components in bonsai namespace (project-based)
            $this->getResourcePath("views/bonsai/components/{$template}/{$componentName}.blade.php"),
            // Fallback: Package default component
            __DIR__ . "/../../templates/components/{$template}/{$componentName}.blade.php",
            // Secondary: Legacy template paths (project-based)
            $this->getResourcePath("templates/{$template}/components/{$componentName}.blade.php"),
            $this->getResourcePath("views/{$template}/components/{$componentName}.blade.php")
        ];

        $output->writeln("Checking possible source paths:");
        foreach ($possiblePaths as $path) {
            $output->writeln("  - {$path}");
            if (file_exists($path)) {
                $output->writeln("✓ Found component at: {$path}");
                
                // Create bonsai template-specific component directory
                $targetDir = $this->getOutputPath("templates/components/{$template}");
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

    protected function detectAndCopyLayout(string $sourcePath, string $templateName, OutputInterface $output): void
    {
        $output->writeln("<info>Detecting and copying layout for template: $templateName</info>");
        
        // Define possible layout paths relative to the source template
        $layoutPaths = [
            // Check in the template-specific layouts directory
            $this->getResourcePath("views/bonsai/layouts/{$templateName}.blade.php"),
            $this->getResourcePath("views/bonsai/layouts/template-{$templateName}.blade.php"),
            // Check in the parent layouts directory
            dirname($sourcePath) . "/../layouts/{$templateName}.blade.php",
            dirname($sourcePath) . "/../layouts/template-{$templateName}.blade.php",
            // Check in the root layouts directory
            dirname($sourcePath) . "/../../layouts/{$templateName}.blade.php",
            dirname($sourcePath) . "/../../layouts/template-{$templateName}.blade.php"
        ];
        
        foreach ($layoutPaths as $layoutPath) {
            $output->writeln("  - Checking: $layoutPath");
            if (file_exists($layoutPath)) {
                $output->writeln("<info>Found layout at: $layoutPath</info>");
                
                // Create layouts directory if it doesn't exist
                $targetLayoutDir = $this->getTargetPath("templates/layouts");
                if (!is_dir($targetLayoutDir)) {
                    mkdir($targetLayoutDir, 0755, true);
                }
                
                // Copy the layout file
                $targetLayoutPath = $targetLayoutDir . "/template-{$templateName}.blade.php";
                copy($layoutPath, $targetLayoutPath);
                $output->writeln("<info>✓ Copied layout to: $targetLayoutPath</info>");
                return;
            }
        }
        
        $output->writeln("<comment>No layout file found for template: $templateName</comment>");
    }
} 