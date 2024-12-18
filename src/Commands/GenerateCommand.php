<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Jackalopelabs\BonsaiCli\Traits\HandlesTemplatePaths;

class GenerateCommand extends Command
{
    use HandlesTemplatePaths;

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

        try {
            $this->info("\n🌳 Starting Bonsai generation process...");
            $this->info("Template: {$template}");
            $this->info("Config Path: {$configPath}");

            $config = $this->loadConfig($configPath);

            // Debug configuration
            $this->info("\nConfiguration loaded:");
            $this->info("- Name: " . ($config['name'] ?? 'Not set'));
            $this->info("- Components: " . json_encode($config['components'] ?? []));
            $this->info("- Sections count: " . count($config['sections'] ?? []));
            $this->info("- Layouts: " . json_encode($config['layouts'] ?? []));
            $this->info("- Pages: " . json_encode($config['pages'] ?? []));

            // Verify the home page configuration
            if (isset($config['pages']['home'])) {
                $this->info("\nHome page configuration:");
                $this->info("- Title: " . ($config['pages']['home']['title'] ?? 'Not set'));
                $this->info("- Layout: " . ($config['pages']['home']['layout'] ?? 'Not set'));
                $this->info("- Is Homepage: " . (!empty($config['pages']['home']['is_homepage']) ? 'Yes' : 'No'));
            } else {
                $this->warn("\nNo home page configuration found");
            }

            // Check Heroicons setup before generating components
            $hasHeroicons = $this->checkHeroiconsSetup();

            // Execute generation steps in sequence
            $this->generateComponents($config['components'] ?? [], $hasHeroicons);
            $this->generateSections($config['sections'] ?? []);
            $this->generateLayouts($config['layouts'] ?? []);
            $this->generateSitePages($config['pages'] ?? []);
            $this->generateDatabase($config['database'] ?? []);
            $this->configureSettings($config['settings'] ?? []);

            $this->displaySuccessMessage($template);

        } catch (\Exception $e) {
            $this->error("Error generating site: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    protected function getConfigPath($template)
    {
        $rootPath = $this->getLaravel()->basePath();
        $this->info("Project root path: {$rootPath}");

        $paths = [
            "{$rootPath}/config/bonsai/templates/{$template}.yml",
            "{$rootPath}/config/bonsai/{$template}.yml",
            "{$rootPath}/config/templates/{$template}.yml",
            __DIR__ . "/../../config/templates/{$template}.yml"
        ];

        $this->info("Checking possible config paths:");
        foreach ($paths as $path) {
            $this->info("Checking: {$path}");
            if (file_exists($path)) {
                $this->info("Found configuration at: {$path}");
                return $path;
            } else {
                $this->info("Not found at: {$path}");
            }
        }

        throw new \Exception("Configuration file not found for template: {$template}");
    }

    protected function loadConfig($path)
    {
        $this->info("Attempting to load config from: {$path}");

        if (!file_exists($path)) {
            $this->error("File does not exist at path: {$path}");
            throw new \Exception("Configuration file not found: {$path}");
        }

        if (!is_readable($path)) {
            $this->error("File exists but is not readable: {$path}");
            throw new \Exception("Configuration file is not readable: {$path}");
        }

        try {
            $contents = file_get_contents($path);
            $this->info("Successfully read file contents");

            $config = Yaml::parse($contents);
            $this->info("Successfully parsed YAML configuration");

            return $config;
        } catch (\Exception $e) {
            $this->error("Error parsing configuration: " . $e->getMessage());
            throw $e;
        }
    }

    protected function generateComponents($components, $hasHeroicons = false)
    {
        $this->info('Generating components...');

        if (!$hasHeroicons) {
            $this->info('Using fallback SVG icons since Heroicons is not properly configured.');
        }

        putenv("BONSAI_HAS_HEROICONS=" . ($hasHeroicons ? "true" : "false"));

        if (isset($components[0])) {
            $components = array_filter($components, function($component) {
                return in_array($component, [
                    'hero',
                    'header',
                    'card',
                    'widget',
                    'accordion',
                    'cta',
                    'list-item',
                    'pricing-box'
                ]);
            });
            $components = array_combine($components, array_fill(0, count($components), []));
        }

        foreach ($components as $component => $config) {
            try {
                $componentName = is_array($config) ? $component : $config;
                $this->info("Installing component: {$componentName}");
                $this->copyComponentTemplate($componentName);

                switch ($componentName) {
                    case 'card':
                        $this->copyComponentIcon('flowchart');
                        break;
                    case 'widget':
                        $this->copyComponentTemplate('accordion');
                        $this->copyComponentTemplate('cta');
                        $this->copyComponentTemplate('list-item');
                        break;
                    case 'pricing-box':
                        $this->info("Installing pricing component");
                        break;
                }

            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate component '{$componentName}': " . $e->getMessage());
            }
        }
    }

    protected function copyComponentTemplate($componentName)
    {
        $possiblePaths = [
            base_path("templates/components/{$componentName}.blade.php"),
            base_path("templates/components/icons/{$componentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$componentName}.blade.php",
            __DIR__ . "/../../templates/components/icons/{$componentName}.blade.php",
            base_path("resources/views/bonsai/components/{$componentName}.blade.php")
        ];

        $templatePath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $templatePath = $path;
                $this->info("Found template at: {$path}");
                break;
            }
        }

        if (!$templatePath) {
            $this->warn("No template found for component: {$componentName}");
            $this->createBasicComponent($componentName);
            return;
        }

        $isIcon = strpos($templatePath, '/icons/') !== false;
        $isSubComponent = in_array($componentName, ['accordion', 'cta', 'list-item']);

        $targetDir = match(true) {
            $isIcon => resource_path("views/bonsai/components/icons"),
            $isSubComponent => resource_path("views/bonsai/components"),
            default => resource_path("views/bonsai/components")
        };

        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
            $this->info("Created directory: {$targetDir}");
        }

        $targetPath = "{$targetDir}/" . basename($templatePath);
        $this->files->copy($templatePath, $targetPath);
        $this->line("Component " . ($isIcon ? "icon " : "") . "installed at: {$targetPath}");

        $this->info("Component details:");
        $this->info("- Type: " . ($isIcon ? 'icon' : ($isSubComponent ? 'subcomponent' : 'component')));
        $this->info("- Source: {$templatePath}");
        $this->info("- Target: {$targetPath}");
    }

    protected function createBasicComponent($name)
    {
        $targetPath = base_path("resources/views/bonsai/components/{$name}.blade.php");
        $content = <<<BLADE
<div class="component-{$name}">
    <!-- Basic {$name} component -->
    <div class="p-4">
        <h2>{{ \$title ?? 'Default Title' }}</h2>
        {{ \$slot }}
    </div>
</div>
BLADE;

        $this->files->put($targetPath, $content);
        $this->info("Created basic component at: {$targetPath}");
    }

    protected function generateSections($sections)
    {
        $this->info('Generating sections...');

        foreach ($sections as $section => $config) {
            try {
                $this->info("Creating section: {$section}");
                $componentType = $config['component'] ?? $section;

                $sectionPath = resource_path("views/bonsai/sections/{$section}.blade.php");
                if (!$this->files->exists(dirname($sectionPath))) {
                    $this->files->makeDirectory(dirname($sectionPath), 0755, true);
                }

                // Use single :data prop approach here
                $sectionContent = $this->generateSectionContent($section, $componentType, $config['data'] ?? []);

                $this->files->put($sectionPath, $sectionContent);
                $this->info("✓ Section created successfully: {$sectionPath}");

            } catch (\Exception $e) {
                $this->error("Failed to generate section '{$section}': " . $e->getMessage());
            }
        }
    }

    protected function generateSectionContent($section, $componentType, $data)
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

        $template = <<<BLADE
@props([
    'class' => ''
])

@php
\${$dataVarName} = [
BLADE;

        $template .= implode("\n", $dataLines) . "\n];\n@endphp\n\n";
        $template .= "<div class=\"{{ \$class }}\">\n";
        $template .= "    <x-{$componentType} :data=\"\${$dataVarName}\" />\n";
        $template .= "</div>\n";

        return $template;
    }

    protected function generateLayouts($layouts)
    {
        $this->info('Generating layouts...');

        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        $themeSettings = $config['theme'] ?? [
            'body' => ['class' => 'bg-gray-100'],
            'header' => ['class' => 'bg-opacity-60 backdrop-blur-md shadow-lg border border-transparent rounded-full mx-auto p-1 my-4']
        ];

        foreach ($layouts as $layout => $layoutConfig) {
            try {
                $layoutPath = resource_path("views/bonsai/layouts/{$layout}.blade.php");
                if (!$this->files->exists(dirname($layoutPath))) {
                    $this->files->makeDirectory(dirname($layoutPath), 0755, true);
                }

                $layoutContent = <<<BLADE
<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @include('utils.styles')
    </head>

    <body @php(body_class('{$themeSettings['body']['class']}'))>
        @php(wp_body_open())

        <div id="app">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>

            @include('bonsai.sections.site_header')

            <main id="main" class="max-w-5xl mx-auto">
                <div class="{{ \$containerInnerClasses }}">
                    @yield('content')
                </div>
            </main>

            @includeIf('sections.footer')
        </div>

        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html>
BLADE;

                $this->files->put($layoutPath, $layoutContent);
                $this->info("Created layout file: {$layoutPath}");

            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate layout '{$layout}': " . $e->getMessage());
            }
        }
    }

    protected function generateSitePages($pages)
    {
        $this->info('Generating pages...');

        foreach ($pages as $slug => $config) {
            try {
                $title = $config['title'] ?? Str::title($slug);
                $layout = $config['layout'] ?? 'default';

                $this->info("Creating page '{$title}' with layout '{$layout}'");

                $templateContent = $this->generateTemplateContent($slug, $layout, $config);
                $templatePath = resource_path("views/bonsai/templates/template-{$layout}.blade.php");

                if (!$this->files->exists(dirname($templatePath))) {
                    $this->files->makeDirectory(dirname($templatePath), 0755, true);
                }

                $this->files->put($templatePath, $templateContent);
                $this->info("Created template file: {$templatePath}");

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

                if (is_wp_error($pageId)) {
                    throw new \Exception("Failed to create page: " . $pageId->get_error_message());
                }

                $this->info("Page created with ID: {$pageId}");

                if (!empty($config['is_homepage'])) {
                    $this->info("Setting as homepage...");
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $pageId);
                    $this->info("Homepage set successfully");
                }

            } catch (\Exception $e) {
                $this->error("Failed to generate page '{$slug}': " . $e->getMessage());
            }
        }
    }

    protected function generateTemplateContent($template, $layout, $config)
    {
        $layoutSections = $this->getLayoutSections($layout);

        $contentSections = array_filter($layoutSections, fn($section) => $section !== 'site_header');

        $templateName = ucfirst($template);

        $sectionIncludes = collect($contentSections)
            ->map(fn($section) => "@include('bonsai.sections.{$section}')")
            ->implode("\n    ");

        return <<<BLADE
{{--
    Template Name: {$templateName} Template
--}}
@extends('bonsai.layouts.{$layout}')

@section('content')
    {$sectionIncludes}
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
        if (empty($database)) {
            return;
        }

        $this->info('Configuring database...');

        if (!empty($database['seeds'])) {
            foreach ($database['seeds'] as $seeder) {
                try {
                    if (!class_exists("Database\\Seeders\\{$seeder}")) {
                        $this->warn("Seeder not found, skipping: {$seeder}");
                        continue;
                    }
                    $this->call('db:seed', ['--class' => $seeder]);
                } catch (\Exception $e) {
                    $this->warn("Failed to run seeder {$seeder}: " . $e->getMessage());
                }
            }
        }

        if (!empty($database['imports'])) {
            foreach ($database['imports'] as $import) {
                try {
                    if (str_ends_with($import, '.sql')) {
                        if (!file_exists($import)) {
                            $this->warn("SQL file not found, skipping: {$import}");
                            continue;
                        }
                        $this->importSqlFile($import);
                    }
                } catch (\Exception $e) {
                    $this->warn("Failed to import {$import}: " . $e->getMessage());
                }
            }
        }
    }

    protected function configureSettings($settings)
    {
        if (empty($settings)) {
            return;
        }

        $this->info('Configuring site settings...');

        $currentTemplate = get_option('template');
        $currentStylesheet = get_option('stylesheet');

        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        if (isset($config['name'])) {
            update_option('blogname', $config['name']);
            $this->info("Updated site name to: {$config['name']}");
        }

        foreach ($settings['options'] ?? [] as $option => $value) {
            if (in_array($option, ['template', 'stylesheet', 'current_theme']) ||
                strpos($option, 'theme_mods_') === 0) {
                $this->line("Skipping theme setting: {$option}");
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

        if (get_option('template') !== $currentTemplate) {
            $this->warn("Theme template was modified, restoring to: {$currentTemplate}");
            update_option('template', $currentTemplate);
        }

        if (get_option('stylesheet') !== $currentStylesheet) {
            $this->warn("Theme stylesheet was modified, restoring to: {$currentStylesheet}");
            update_option('stylesheet', $currentStylesheet);
        }
    }

    protected function updateEnvFile($envVars)
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

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

        if (!file_exists($sourcePath)) {
            $this->error("Icon not found: {$sourcePath}");
            return;
        }

        $targetDir = resource_path("views/bonsai/components/icons");
        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
            $this->info("Created icons directory: {$targetDir}");
        }

        $targetPath = "{$targetDir}/{$iconName}.blade.php";
        $this->files->copy($sourcePath, $targetPath);
        $this->info("Installed icon at: {$targetPath}");
    }

    protected function checkHeroiconsSetup()
    {
        $this->info('Checking Heroicons setup...');

        if (!class_exists(\BladeUI\Icons\BladeIconsServiceProvider::class)) {
            $this->warn('⚠️  blade-icons package not found.');
            $this->warn('Run: composer require blade-ui-kit/blade-icons');
            return false;
        }

        if (!class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->warn('⚠️  blade-heroicons package not found.');
            $this->warn('Run: composer require blade-ui-kit/blade-heroicons');
            return false;
        }

        if (!file_exists(config_path('blade-icons.php'))) {
            $this->info('Publishing blade-icons configuration...');
            $this->call('vendor:publish', [
                '--tag' => 'blade-icons'
            ]);

            $configPath = config_path('blade-icons.php');
            if (file_exists($configPath)) {
                $config = file_get_contents($configPath);
                $config = str_replace(
                    "'path' => public_path('icons')",
                    "'path' => resource_path('images/icons')",
                    $config
                );
                file_put_contents($configPath, $config);
                $this->info('Updated blade-icons config with correct paths.');
            }
        }

        $appConfig = config_path('app.php');
        if (file_exists($appConfig)) {
            $content = file_get_contents($appConfig);
            $providers = [
                \BladeUI\Icons\BladeIconsServiceProvider::class,
                \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            ];

            $modified = false;
            foreach ($providers as $provider) {
                if (strpos($content, $provider) === false) {
                    $pattern = "/('providers' => \[\s*)(.*?)(\s*\])/s";
                    if (preg_match($pattern, $content, $matches)) {
                        $newContent = $matches[1] . $matches[2] . "        " . $provider . "::class,\n" . $matches[3];
                        $content = str_replace($matches[0], $newContent, $content);
                        $modified = true;
                    }
                }
            }

            if ($modified) {
                file_put_contents($appConfig, $content);
                $this->info('Added Blade Icons providers to config/app.php');
            }
        }

        $iconsPath = resource_path('images/icons');
        if (!is_dir($iconsPath)) {
            mkdir($iconsPath, 0755, true);
            $this->info("Created icons directory at: {$iconsPath}");
        }

        if (app()->environment('production') && !cache()->has('blade-icons')) {
            $this->call('icons:cache');
        }

        return true;
    }

    protected function displaySuccessMessage($template)
    {
        $this->info("🌳 Successfully generated {$template} template!");
        $this->line('');
        $this->info('Run `npm run dev` to compile assets.');
    }

    protected function importSqlFile($file)
    {
        // Implement if needed
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
            } elseif (is_int($value) || is_float($value)) {
                $output .= $value;
            } else {
                $output .= "'" . addslashes($value) . "'";
            }
            
            $output .= ",\n";
        }
        
        $output .= $indent . "]";
        return $output;
    }
}
