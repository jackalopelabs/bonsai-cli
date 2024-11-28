<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class GenerateCommand extends Command
{
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
            $config = $this->loadConfig($configPath);
            
            $this->info("Starting Bonsai site generation for template: {$template}");
            $this->info("Using config: {$configPath}");
            
            // Execute generation steps in sequence
            $this->generateComponents($config['components'] ?? []);
            $this->generateSections($config['sections'] ?? []);
            $this->generateLayouts($config['layouts'] ?? []);
            $this->generatePages($config['pages'] ?? []);
            $this->generateDatabase($config['database'] ?? []);
            $this->configureSettings($config['settings'] ?? []);
            
            $this->info('🌳 Bonsai site generation completed successfully!');
            
        } catch (\Exception $e) {
            $this->error("Error generating site: " . $e->getMessage());
            return 1;
        }
    }

    protected function getConfigPath($template)
    {
        // Check locations in order of priority
        $paths = [
            base_path("config/bonsai/{$template}.yml"),          // 1. Local project config
            __DIR__ . "/../../config/templates/{$template}.yml"  // 2. Default package config
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

        return Yaml::parseFile($path);
    }

    protected function generateComponents($components)
    {
        $this->info('Generating components...');
        
        // If components is a simple array, convert to associative
        if (isset($components[0])) {
            $components = array_filter($components, function($component) {
                return in_array($component, ['hero', 'header', 'card']);
            });
            $components = array_combine($components, array_fill(0, count($components), []));
        }

        foreach ($components as $component => $config) {
            try {
                $componentName = is_array($config) ? $component : $config;
                $this->info("Installing component: {$componentName}");

                // Copy the component template
                $this->copyComponentTemplate($componentName);

                // If this is the card component, also copy its required icons
                if ($componentName === 'card') {
                    $this->info("Installing card component icons...");
                    $this->copyComponentIcon('flowchart');
                }

            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate component '{$componentName}': " . $e->getMessage());
            }
        }
    }

    protected function copyComponentTemplate($componentName)
    {
        // Update possible paths to include icons
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

        // Determine if this is an icon component
        $isIcon = strpos($templatePath, '/icons/') !== false || strpos($componentName, 'icon-') === 0;
        
        // Set the target directory based on component type
        $targetDir = $isIcon 
            ? resource_path("views/bonsai/components/icons")
            : resource_path("views/bonsai/components");

        // Ensure directory exists
        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
            $this->info("Created directory: {$targetDir}");
        }

        // Get just the filename for icons, removing the 'icon-' prefix
        $filename = $isIcon 
            ? str_replace('icon-', '', basename($templatePath))
            : basename($templatePath);

        // Copy component to appropriate directory
        $targetPath = "{$targetDir}/{$filename}";
        $this->files->copy($templatePath, $targetPath);
        $this->line("Component " . ($isIcon ? "icon " : "") . "installed at: {$targetPath}");

        // Debug info
        $this->info("Component details:");
        $this->info("- Is icon: " . ($isIcon ? 'yes' : 'no'));
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
        
        // Set the template name in environment for SectionCommand
        $template = $this->argument('template');
        putenv("BONSAI_TEMPLATE={$template}");
        
        foreach ($sections as $section => $config) {
            try {
                $this->info("Creating section: {$section}");
                $componentType = $config['component'] ?? $section;
                
                // Pass data directly to section command
                if (isset($config['data'])) {
                    foreach ($config['data'] as $key => $value) {
                        if (is_array($value)) {
                            putenv("BONSAI_DATA_{$key}=" . json_encode($value));
                        } else {
                            putenv("BONSAI_DATA_{$key}={$value}");
                        }
                    }
                }

                $this->call('bonsai:section', [
                    'name' => $section,
                    '--component' => $componentType,
                    '--default' => true
                ]);
                
                // Clear environment variables
                if (isset($config['data'])) {
                    foreach ($config['data'] as $key => $value) {
                        putenv("BONSAI_DATA_{$key}");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("Failed to generate section '{$section}': " . $e->getMessage());
            }
        }
        
        // Clear template from environment
        putenv("BONSAI_TEMPLATE");
    }

    protected function generateLayouts($layouts)
    {
        $this->info('Generating layouts...');
        
        foreach ($layouts as $layout => $config) {
            try {
                // Create the layout file
                $layoutPath = resource_path("views/bonsai/layouts/{$layout}.blade.php");
                
                // Ensure the layouts directory exists
                if (!$this->files->exists(dirname($layoutPath))) {
                    $this->files->makeDirectory(dirname($layoutPath), 0755, true);
                }
                
                // Generate the layout content
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

    <body @php(body_class('bg-gray-100'))>
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

            @include('sections.footer')
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

    protected function generatePages($pages)
    {
        $this->info('Generating pages...');
        
        // Get the template name
        $template = $this->argument('template');
        
        foreach ($pages as $slug => $config) {
            try {
                $title = $config['title'] ?? Str::title($slug);
                $layout = $config['layout'] ?? 'default';
                
                // Create template file
                $templatePath = resource_path("views/template-{$template}.blade.php");
                $templateContent = $this->generateTemplateContent($template, $layout, $config);
                $this->files->put($templatePath, $templateContent);
                $this->info("Template file created at: {$templatePath}");

                // Create or update the page in WordPress
                $pageId = wp_insert_post([
                    'post_title'   => $title,
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'meta_input'   => [
                        '_wp_page_template' => "template-{$template}.blade.php",
                    ],
                ]);

                if (is_wp_error($pageId)) {
                    throw new \Exception("Failed to create page: " . $pageId->get_error_message());
                }

                $this->info("Page '{$title}' created with ID: {$pageId}");

                // Set as homepage if specified
                if (isset($config['is_homepage']) && $config['is_homepage']) {
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $pageId);
                    $this->info("Set '{$title}' as static homepage");
                }

            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate page '{$slug}': " . $e->getMessage());
            }
        }
    }

    protected function generateTemplateContent($template, $layout, $config)
    {
        // Get layout sections from configuration
        $layoutSections = $this->getLayoutSections($layout);
        
        // Generate section includes
        $sectionIncludes = collect($layoutSections)
            ->map(fn($section) => "@include('bonsai.sections.{$section}')")
            ->implode("\n    ");

        return <<<BLADE
{{--
    Template Name: {$template} Template
--}}
@extends('bonsai.layouts.{$layout}')

@section('content')
    @include('bonsai.sections.home_hero')
    @include('bonsai.sections.services_card')
@endsection
BLADE;
    }

    protected function getLayoutSections($layoutName)
    {
        // Get the template name and config
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);
        
        // Get sections from the layout configuration
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
                    // Check if seeder class exists
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
    
        // Get current theme settings before we start
        $currentTemplate = get_option('template');
        $currentStylesheet = get_option('stylesheet');
    
        // Get the template name and config
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);
    
        // Update site name based on template configuration
        if (isset($config['name'])) {
            update_option('blogname', $config['name']);
            $this->info("Updated site name to: {$config['name']}");
        }
    
        // WordPress options
        foreach ($settings['options'] ?? [] as $option => $value) {
            // Skip theme-related settings
            if (in_array($option, ['template', 'stylesheet', 'current_theme']) || 
                strpos($option, 'theme_mods_') === 0) {
                $this->line("Skipping theme setting: {$option}");
                continue;
            }
            
            update_option($option, $value);
        }
    
        // Environment variables
        if (!empty($settings['env'])) {
            $this->updateEnvFile($settings['env']);
        }
    
        // API keys and credentials
        if (!empty($settings['api_keys'])) {
            $this->storeApiKeys($settings['api_keys']);
        }
    
        // Verify theme settings haven't changed
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
            // Store API keys in .env
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

        // Create the icons directory if it doesn't exist
        $targetDir = resource_path("views/bonsai/components/icons");
        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
            $this->info("Created icons directory: {$targetDir}");
        }

        // Copy the icon file
        $targetPath = "{$targetDir}/{$iconName}.blade.php";
        $this->files->copy($sourcePath, $targetPath);
        $this->info("Installed icon at: {$targetPath}");
    }
}