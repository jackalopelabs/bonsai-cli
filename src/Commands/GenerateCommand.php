<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Jackalopelabs\BonsaiCli\Traits\HandlesTemplatePaths;
use Jackalopelabs\BonsaiCli\Traits\BuildSystemDetector;

class GenerateCommand extends Command
{
    use HandlesTemplatePaths, BuildSystemDetector;

    protected $signature = 'bonsai:generate {template} {--config=}';
    protected $description = 'Generate a complete Bonsai site from a template configuration';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);

        $this->info("🌳 Starting Bonsai generation for template: {$template}");

        $config = $this->loadConfig($configPath);
        $hasHeroicons = $this->checkHeroiconsSetup();

        // Copy template assets first
        $this->copyTemplateAssets($template);

        $this->generateComponents($config['components'] ?? [], $hasHeroicons);
        $this->generateSections($config['sections'] ?? []);
        $this->generateLayouts($config['layouts'] ?? []);
        $this->generateSitePages($config['pages'] ?? []);
        $this->generateDatabase($config['database'] ?? []);
        $this->configureSettings($config['settings'] ?? []);

        $this->displaySuccessMessage($template);
        return 0;
    }

    protected function getConfigPath($template)
    {
        $rootPath = $this->getLaravel()->basePath();
        $paths = [
            "{$rootPath}/config/bonsai/templates/{$template}.yml",
            "{$rootPath}/config/bonsai/{$template}.yml",
            "{$rootPath}/config/templates/{$template}.yml",
            __DIR__ . "/../../config/templates/{$template}.yml"
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \Exception("Configuration file not found for template: {$template}");
    }

    protected function loadConfig($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("Configuration file not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new \Exception("Configuration file is not readable: {$path}");
        }

        $contents = file_get_contents($path);
        return Yaml::parse($contents);
    }

    protected function generateComponents($components, $hasHeroicons = false)
    {
        $this->info("\n🔄 Starting component generation process...");
        putenv("BONSAI_HAS_HEROICONS=" . ($hasHeroicons ? "true" : "false"));
        $template = $this->argument('template');
        
        $this->info("📦 Template: {$template}");
        $this->info("🦸 Heroicons enabled: " . ($hasHeroicons ? "yes" : "no"));

        // Define component dependencies
        $dependencies = [
            'widget' => ['accordion', 'cta', 'list-item'],
            'card' => ['icons.flowchart'],
            'hero' => ['icons.github'],
        ];

        // Get all components from sections that use namespaced components
        $configPath = $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);
        $namespacedComponents = [];
        
        foreach ($config['sections'] ?? [] as $section => $sectionConfig) {
            if (isset($sectionConfig['component']) && str_contains($sectionConfig['component'], '.')) {
                $namespacedComponents[] = $sectionConfig['component'];
            }
        }

        // Merge with explicitly defined components
        if (isset($components[0])) {
            $this->info("\nProcessing array-style component list");
            $components = array_filter($components, function($c) {
                return in_array($c, [
                    'hero','header','card','widget','accordion',
                    'cta','list-item','pricing-box','feature-grid'
                ]);
            });
            $components = array_combine($components, array_fill(0, count($components), []));
        }

        // Add namespaced components to the components list
        foreach ($namespacedComponents as $component) {
            if (!isset($components[$component])) {
                $components[$component] = [];
            }
        }

        $this->info("\nComponents to process: " . implode(', ', array_keys($components)));

        // First pass: Copy main components
        $this->info("\n=== First Pass: Main Components ===");
        foreach ($components as $component => $config) {
            $componentName = is_array($config) ? $component : $config;
            $this->info("\n🔨 Processing component: {$componentName}");
            
            // Log target paths
            $targetPath = resource_path("views/bonsai/components/{$template}/{$componentName}.blade.php");
            $this->info("Target path: {$targetPath}");
            
            if (!$this->copyTemplateComponent($componentName)) {
                $this->info("⚠️ No template-specific component found, trying core components...");
                if ($this->copyBonsaiComponent($componentName)) {
                    $this->info("✓ Copied from core components");
                    $this->info("Registering component in template namespace...");
                    $this->registerBonsaiTemplateComponent($template, $componentName);
                    
                    // Verify component file exists
                    if (file_exists($targetPath)) {
                        $this->info("✓ Component file exists at target path");
                    } else {
                        $this->error("❌ Component file not found at target path");
                    }
                } else {
                    $this->warn("❌ Component not found in core components either");
                }
            }
        }

        // Second pass: Process dependencies
        $this->info("\n=== Second Pass: Dependencies ===");
        foreach ($components as $component => $config) {
            $componentName = is_array($config) ? $component : $config;
            if (isset($dependencies[$componentName])) {
                $this->info("\n📦 Installing dependencies for {$componentName}:");
                $this->info("Required dependencies: " . implode(', ', $dependencies[$componentName]));
                
                foreach ($dependencies[$componentName] as $dep) {
                    $this->info("\nProcessing dependency: {$dep}");
                    if (str_contains($dep, '.')) {
                        // Handle nested components like icons
                        list($folder, $name) = explode('.', $dep);
                        $this->info("Copying icon dependency: {$name} to icons folder");
                        $this->copyComponentIcon($name);
                        
                        // Verify icon file exists
                        $iconPath = resource_path("views/bonsai/components/icons/{$name}.blade.php");
                        if (file_exists($iconPath)) {
                            $this->info("✓ Icon file exists at: {$iconPath}");
                        } else {
                            $this->error("❌ Icon file not found at: {$iconPath}");
                        }
                    } else {
                        // Copy and register the dependency in the template namespace
                        $this->info("Copying and registering regular dependency: {$dep}");
                        if ($this->copyBonsaiComponent($dep)) {
                            $this->info("Registering dependency in template namespace...");
                            $this->registerBonsaiTemplateComponent($template, $dep);
                            
                            // Verify dependency file exists
                            $depPath = resource_path("views/bonsai/components/{$template}/{$dep}.blade.php");
                            if (file_exists($depPath)) {
                                $this->info("✓ Dependency file exists at: {$depPath}");
                            } else {
                                $this->error("❌ Dependency file not found at: {$depPath}");
                            }
                        }
                    }
                }
            }
        }
        
        $this->info("\n🏁 Component generation complete");
        
        // Final verification
        $this->info("\n=== Final Component Verification ===");
        $allComponents = array_merge(
            array_keys($components),
            array_reduce($dependencies, function($carry, $deps) {
                return array_merge($carry, array_filter($deps, function($dep) {
                    return !str_contains($dep, '.');
                }));
            }, [])
        );
        
        foreach (array_unique($allComponents) as $comp) {
            if (str_contains($comp, '.')) {
                // Handle namespaced components
                list($namespace, $name) = explode('.', $comp);
                $path = resource_path("views/bonsai/components/{$namespace}/{$name}.blade.php");
            } else {
                $path = resource_path("views/bonsai/components/{$template}/{$comp}.blade.php");
            }
            $this->info(file_exists($path) 
                ? "✓ {$comp}: Found at {$path}" 
                : "❌ {$comp}: Missing from {$path}");
        }
    }

    protected function copyTemplateComponent($componentName)
    {
        $template = $this->argument('template');
        $this->info("\n🔨 Processing component: {$componentName}");

        // Handle namespaced components (e.g., cypress.header)
        $componentParts = explode('.', $componentName);
        $namespace = count($componentParts) > 1 ? $componentParts[0] : $template;
        $baseComponentName = count($componentParts) > 1 ? $componentParts[1] : $componentName;

        // Build source paths with priority for template-specific components
        $sourcePaths = [
            // First check template-specific directory
            base_path("templates/components/{$namespace}/{$baseComponentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$namespace}/{$baseComponentName}.blade.php",
            // Then check root components directory
            base_path("templates/components/{$baseComponentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$baseComponentName}.blade.php"
        ];

        $targetPath = resource_path("views/bonsai/components/{$namespace}/{$baseComponentName}.blade.php");
        $this->info("Target path: {$targetPath}");

        // Find first existing source
        $sourcePath = null;
        foreach ($sourcePaths as $path) {
            if (file_exists($path)) {
                $sourcePath = $path;
                $this->info("Found source at: {$path}");
                break;
            }
        }

        if (!$sourcePath) {
            $this->warn("Component template not found: {$componentName}");
            return false;
        }

        // Ensure target directory exists
        $targetDir = dirname($targetPath);
        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
            $this->info("Created directory: {$targetDir}");
        }

        // Copy the component
        $this->files->copy($sourcePath, $targetPath);
        $this->info("✓ Copied component {$componentName} to {$targetPath}");

        // Register the component
        $this->registerBonsaiTemplateComponent($namespace, $baseComponentName);

        return true;
    }

    protected function registerBonsaiTemplateComponent($namespace, $componentName)
    {
        $this->info("\n=== Registering Component: {$namespace}.{$componentName} ===");
        
        try {
            // Register with namespace
            $viewPath = "bonsai.components.{$namespace}.{$componentName}";
            $alias = "bonsai::{$namespace}.{$componentName}";
            \Illuminate\Support\Facades\Blade::component($viewPath, $alias);
            $this->info("✓ Registered component with namespace: <x-{$alias}>");
            
            // Also register without namespace for backward compatibility
            $backwardAlias = "bonsai::{$componentName}";
            \Illuminate\Support\Facades\Blade::component($viewPath, $backwardAlias);
            $this->info("✓ Registered component with backward compatibility: <x-{$backwardAlias}>");
            
            return true;
        } catch (\Exception $e) {
            $this->error("❌ Failed to register component: " . $e->getMessage());
            return false;
        }
    }

    protected function copyBonsaiComponent($componentName)
    {
        $possiblePaths = [
            base_path("templates/components/{$componentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$componentName}.blade.php",
            base_path("resources/views/bonsai/components/{$componentName}.blade.php")
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $template = $this->argument('template');
                
                // Create template-specific directory
                $templateDir = resource_path("views/bonsai/components/{$template}");
                if (!$this->files->exists($templateDir)) {
                    $this->files->makeDirectory($templateDir, 0755, true);
                }
                
                // Copy directly to template-specific directory
                $templatePath = "{$templateDir}/{$componentName}.blade.php";
                $this->files->copy($path, $templatePath);
                
                // Register only the template-specific component
                $this->registerBonsaiTemplateComponent($template, $componentName);
                
                return true;
            }
        }

        // If no template found, create a basic one in the template directory
        $this->createBasicComponent($componentName, $this->argument('template'));
        return true;
    }

    protected function createBasicComponent($name, $template = null)
    {
        $targetPath = $template 
            ? resource_path("views/bonsai/components/{$template}/{$name}.blade.php")
            : resource_path("views/bonsai/components/{$name}.blade.php");

        // Ensure the directory exists
        $this->files->makeDirectory(dirname($targetPath), 0755, true, true);

        $content = <<<BLADE
<div {{ \$attributes->merge(['class' => "component-{$name}"]) }}>
    <div class="p-4">
        <h2>{{ \$title ?? 'Default Title' }}</h2>
        {{ \$slot }}
    </div>
</div>
BLADE;

        $this->files->put($targetPath, $content);
    }

    protected function generateSections($sections)
    {
        foreach ($sections as $section => $config) {
            $componentType = $config['component'] ?? $section;
            $type = explode('_', $section)[0];
            
            // Generate the section in the bonsai sections directory
            $fullPath = resource_path("views/bonsai/sections/{$section}.blade.php");
            
            if (!$this->files->exists(dirname($fullPath))) {
                $this->files->makeDirectory(dirname($fullPath), 0755, true);
            }

            $sectionContent = $this->generateSectionContent($componentType, $section, $config['data'] ?? []);
            $this->files->put($fullPath, $sectionContent);
            
            $this->info("Generated section: bonsai/sections/{$section}");
        }
    }

    protected function generateSectionContent($componentType, $section, $data)
    {
        $dataVarName = Str::camel($section) . "Data";

        // Handle namespaced components (e.g., cypress.header)
        $componentParts = explode('.', $componentType);
        $namespace = count($componentParts) > 1 ? $componentParts[0] : null;
        $baseComponentName = count($componentParts) > 1 ? $componentParts[1] : $componentType;

        // Build the section content
        $content = "@props([\n    'class' => ''\n])\n\n";
        $content .= "@php\n";
        $content .= "\${$dataVarName} = " . $this->arrayToPhpString($data) . ";\n";
        $content .= "@endphp\n\n";
        $content .= "<div class=\"{{ \$class }}\">\n";
        
        // Use namespaced component if available
        if ($namespace) {
            $content .= "    <x-bonsai::{$namespace}.{$baseComponentName} :data=\"\${$dataVarName}\" />\n";
        } else {
            $content .= "    <x-bonsai::{$baseComponentName} :data=\"\${$dataVarName}\" />\n";
        }
        
        $content .= "</div>";

        return $content;
    }

    protected function arrayToPhpString($array, $depth = 0)
    {
        $indent = str_repeat('    ', $depth);
        $output = "[\n";
        
        foreach ($array as $key => $value) {
            $output .= $indent . "    ";
            
            if (is_string($key)) {
                $output .= "'" . addslashes($key) . "' => ";
            }

            if (is_array($value)) {
                $output .= $this->arrayToPhpString($value, $depth + 1);
            } elseif (is_bool($value)) {
                $output .= $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $output .= 'null';
            } elseif (is_string($value)) {
                // Check if the string contains SVG content
                if (strpos($value, '<svg') !== false) {
                    // For SVG content, use single quotes and don't escape internal double quotes
                    $output .= "'" . str_replace("'", "\\'", $value) . "'";
                } else {
                    $output .= "'" . addslashes($value) . "'";
                }
            } else {
                $output .= $value;
            }
            
            $output .= ",\n";
        }
        
        $output .= $indent . "]";
        return $output;
    }

    protected function generateLayouts($layouts)
    {
        $template = $this->argument('template');
        $layoutsPath = resource_path("views/bonsai/{$template}/layouts");

        // Get background images from assets configuration
        $config = $this->loadConfig($this->getConfigPath($template));
        $backgroundImages = $config['assets']['images'] ?? [
            'bonsai_hero_01.webp',
            'bonsai_hero_03.webp'
        ];

        // First image is dark mode, second is light mode
        $darkBgImage = 'resources/images/' . ($backgroundImages[0] ?? 'bonsai_hero_01.webp');
        $lightBgImage = 'resources/images/' . ($backgroundImages[1] ?? 'bonsai_hero_03.webp');

        // Copy background images to the project
        $this->copyLayoutBackgroundImages($template, $backgroundImages);

        if ($this->files->exists($layoutsPath)) {
            $this->info("ℹ Bonsai layouts directory already exists, skipping layout generation");
            return;
        }

        $this->info("ℹ No existing bonsai layout found, generating default layout");
        
        foreach ($layouts as $layout => $layoutConfig) {
            $layoutPath = resource_path("views/bonsai/{$template}/layouts/{$layout}.blade.php");
            if (!$this->files->exists(dirname($layoutPath))) {
                $this->files->makeDirectory(dirname($layoutPath), 0755, true);
            }

            $layoutContent = <<<BLADE
<!doctype html>
<html @php(language_attributes()) x-data="globalData" class="relative h-screen">
    <!-- Hero Background Images -->
    <div class="absolute inset-0 z-0">
        <img src="{{ Vite::asset('{$lightBgImage}') }}"
                alt="Background Light"
                class="w-full h-full object-cover object-top opacity-100"
                style="display: none;"
                x-bind:style="!darkMode ? 'display: block;' : 'display: none;'"
        />
        <img src="{{ Vite::asset('{$darkBgImage}') }}" 
                alt="Background Dark" 
                class="w-full h-full object-cover object-top opacity-100"
                style="display: block;"
                x-bind:style="darkMode ? 'display: block;' : 'display: none;'"
        />
    </div>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('utils.styles')
    </head>

    <body @php(body_class('transition-colors duration-200 p-0 m-0 h-screen')) 
          x-bind:class="darkMode ? 'dark-mode' : 'light-mode'">
        <style>
            body.dark-mode {
                background-color: #060614 !important;
                color: white !important;
            }
            body.light-mode {
                background-color: white !important;
                color: #1e293b !important;
            }
        </style>
        @php(wp_body_open())
        <div id="app" class="relative z-10">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>

            @includeIf('bonsai.{$template}.sections.site_header')

            <main id="main" class="max-w-5xl mx-auto">
                <div class="{{ \$containerInnerClasses ?? 'px-6' }}">
                    @yield('content')
                </div>
            </main>

            @includeIf('bonsai.{$template}.sections.site_footer')
        </div>

        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html>
BLADE;

            $this->files->put($layoutPath, $layoutContent);
            $this->info("✓ Layout {$layout} created at {$layoutPath}");
        }
    }

    protected function copyLayoutBackgroundImages($template, $backgroundImages = null)
    {
        $packageRoot = $this->getPackageRoot();
        $imagesPath = resource_path('images');
        
        if (!$this->files->exists($imagesPath)) {
            $this->files->makeDirectory($imagesPath, 0755, true);
        }

        // If specific background images are provided, copy those
        if ($backgroundImages && is_array($backgroundImages)) {
            foreach ($backgroundImages as $image) {
                $sourcePath = $packageRoot . "/resources/images/{$image}";
                $destinationPath = resource_path("images/{$image}");
                
                // Check if the source image exists in the package
                if ($this->files->exists($sourcePath)) {
                    $this->files->copy($sourcePath, $destinationPath);
                    $this->info("✓ Copied background image: {$image}");
                } else {
                    // If not in package, check if it exists in the project's template directory
                    $templateSourcePath = base_path("resources/images/{$image}");
                    if ($this->files->exists($templateSourcePath)) {
                        $this->files->copy($templateSourcePath, $destinationPath);
                        $this->info("✓ Copied background image from project: {$image}");
                    } else {
                        $this->warn("⚠ Background image not found: {$image}");
                    }
                }
            }
            return;
        }

        // Default fallback images if none specified
        $defaultImages = [
            'bonsai_hero_01.webp',
            'bonsai_hero_03.webp'
        ];

        foreach ($defaultImages as $image) {
            $sourcePath = $packageRoot . "/resources/images/{$image}";
            $destinationPath = resource_path("images/{$image}");
            
            if ($this->files->exists($sourcePath)) {
                $this->files->copy($sourcePath, $destinationPath);
                $this->info("✓ Copied default background image: {$image}");
            } else {
                $this->warn("⚠ Default background image not found: {$image}");
            }
        }
    }

    protected function generateSitePages($pages)
    {
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        // If no pages defined, create a default page using the template
        if (empty($pages)) {
            $pages = [
                $template => [
                    'title' => ucfirst($template),
                    'layout' => $template,
                    'is_homepage' => true,
                    'sections' => array_keys($config['sections'] ?? [])
                ]
            ];
        }

        foreach ($pages as $slug => $pageConfig) {
            $title = $pageConfig['title'] ?? Str::title($slug);
            $layout = $pageConfig['layout'] ?? $template;
            $sections = $pageConfig['sections'] ?? array_keys($config['sections'] ?? []);

            $templateContent = $this->generateTemplateContent($template, $layout, ['sections' => $sections]);
            $templatePath = resource_path("views/bonsai/templates/template-{$layout}.blade.php");

            if (!$this->files->exists(dirname($templatePath))) {
                $this->files->makeDirectory(dirname($templatePath), 0755, true);
            }

            $this->files->put($templatePath, $templateContent);

            $pageId = wp_insert_post([
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => [
                    '_wp_page_template' => "template-{$layout}.blade.php",
                    '_bonsai_generated' => 'true',
                    '_bonsai_template'  => $layout,
                ],
            ]);

            if (!is_wp_error($pageId) && !empty($pageConfig['is_homepage'])) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $pageId);
            }
        }
    }

    protected function generateTemplateContent($template, $layout, $config)
    {
        $sections = $config['sections'] ?? [];
        // Filter out site_header from sections
        $sections = array_filter($sections, function($section) {
            return $section !== 'site_header';
        });
        
        $sectionIncludes = array_map(function($section) {
            return "@include('bonsai.sections.{$section}')";
        }, $sections);

        // Check if we should use bonsai namespace for layout
        $layoutNamespace = file_exists(resource_path("views/bonsai/layouts/{$layout}.blade.php")) 
            ? 'bonsai.layouts' 
            : "bonsai.{$template}.layouts";

        return <<<BLADE
{{-- 
    Template Name: Cypress Template
--}}
@extends('{$layoutNamespace}.{$layout}')

@section('content')
{$this->indent(implode("\n", $sectionIncludes), 4)}
@endsection
BLADE;
    }

    protected function getLayoutSections($layoutName)
    {
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        return $config['layouts'][$layoutName]['sections'] ?? [];
    }

    protected function generateDatabase($database)
    {
        if (empty($database)) return;

        if (!empty($database['seeds'])) {
            foreach ($database['seeds'] as $seeder) {
                if (class_exists("Database\\Seeders\\{$seeder}")) {
                    $this->call('db:seed', ['--class' => $seeder]);
                }
            }
        }

        if (!empty($database['imports'])) {
            foreach ($database['imports'] as $import) {
                if (str_ends_with($import, '.sql') && file_exists($import)) {
                    $this->importSqlFile($import);
                }
            }
        }
    }

    protected function configureSettings($settings)
    {
        if (empty($settings)) return;

        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        if (isset($config['name'])) {
            update_option('blogname', $config['name']);
        }

        foreach ($settings['options'] ?? [] as $option => $value) {
            if (in_array($option, ['template', 'stylesheet', 'current_theme']) ||
                str_starts_with($option, 'theme_mods_')) {
                continue;
            }
            update_option($option, $value);
        }

        if (!empty($settings['env'])) {
            $this->updateEnvFile($settings['env']);
        }

        if (!empty($settings['api_keys'])) {
            $this->storeApiKeys($settings['api_keys']);
        }
    }

    protected function updateEnvFile($envVars)
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return;

        $envContent = file_get_contents($envPath);

        foreach ($envVars as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    protected function storeApiKeys($apiKeys)
    {
        foreach ($apiKeys as $service => $keys) {
            $this->updateEnvFile($keys);
        }
    }

    protected function copyComponentIcon($iconName)
    {
        $sourcePath = __DIR__ . "/../../templates/components/icons/{$iconName}.blade.php";
        if (!file_exists($sourcePath)) return;

        $targetDir = resource_path("views/bonsai/components/icons");
        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
        }

        $targetPath = "{$targetDir}/{$iconName}.blade.php";
        $this->files->copy($sourcePath, $targetPath);
    }

    protected function checkHeroiconsSetup()
    {
        return class_exists(\BladeUI\Icons\BladeIconsServiceProvider::class) &&
               class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class);
    }

    protected function displaySuccessMessage($template)
    {
        $buildSystem = $this->detectBuildSystem();
        $buildCommand = $this->getBuildCommand();
        
        $this->info("🌳 Successfully generated {$template} template! Run `{$buildCommand}` to compile assets.");
    }

    protected function importSqlFile($file)
    {
        // Implement if needed
    }

    protected function indent($content, $spaces = 4)
    {
        $lines = explode("\n", $content);
        $indented = array_map(function($line) use ($spaces) {
            return str_repeat(' ', $spaces) . $line;
        }, $lines);
        return implode("\n", $indented);
    }

    protected function copyTemplateAssets($template)
    {
        // Check both package and local template assets
        $possibleSourceDirs = [
            $this->getPackageRoot() . "/templates/assets/{$template}",
            $this->getBasePath() . "/templates/assets/{$template}"
        ];

        $assetDir = $this->getAssetDirectory();
        $targetDir = $this->getBasePath() . "/{$assetDir}/images";

        // Create images directory if it doesn't exist
        if (!$this->files->isDirectory($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
            $this->info("Created directory: {$targetDir}");
        }

        $assetsFound = false;

        foreach ($possibleSourceDirs as $sourceDir) {
            if ($this->files->isDirectory($sourceDir)) {
                // Copy all files from the template assets directory
                foreach ($this->files->files($sourceDir) as $file) {
                    $filename = $file->getFilename();
                    $targetPath = $targetDir . '/' . $filename;
                    
                    if ($this->files->copy($file->getPathname(), $targetPath)) {
                        $this->info("✓ Copied asset: {$filename} to {$assetDir}/images/");
                        $assetsFound = true;
                    } else {
                        $this->warn("! Failed to copy asset: {$filename}");
                    }
                }
            }
        }

        if (!$assetsFound) {
            $this->warn("! No assets found for template: {$template}");
            $this->info("  Checked directories:");
            foreach ($possibleSourceDirs as $dir) {
                $this->info("  - {$dir}");
            }
        }
    }

    protected function getPackageRoot()
    {
        return dirname(dirname(__DIR__));
    }
}
