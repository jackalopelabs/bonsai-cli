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
        
        // Convert list-style components array to associative if needed
        if (isset($components[0])) {
            $components = array_combine($components, array_fill(0, count($components), []));
        }

        foreach ($components as $component => $config) {
            try {
                $componentName = is_array($config) ? $component : $config;
                $this->info("Installing component: {$componentName}");

                // Check all possible locations for component template
                $possiblePaths = [
                    __DIR__ . "/../../templates/components/{$componentName}.blade.php",
                    base_path("resources/views/components/{$componentName}.blade.php"),
                    base_path("resources/views/components/bonsai/{$componentName}.blade.php")
                ];

                $templatePath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $templatePath = $path;
                        break;
                    }
                }

                if (!$templatePath) {
                    // Create a basic component if template not found
                    $this->createBasicComponent($componentName);
                    continue;
                }

                // Ensure the components directory exists
                $targetDir = base_path("resources/views/components");
                if (!$this->files->exists($targetDir)) {
                    $this->files->makeDirectory($targetDir, 0755, true);
                }

                // Copy component to main components directory
                $targetPath = "{$targetDir}/{$componentName}.blade.php";
                $this->files->copy($templatePath, $targetPath);
                $this->line("Component installed at: {$targetPath}");

            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate component '{$componentName}': " . $e->getMessage());
            }
        }
    }

    protected function createBasicComponent($name)
    {
        $targetPath = base_path("resources/views/components/{$name}.blade.php");
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
                $this->info("Component: " . ($config['component'] ?? $section));
                
                // Get the component type
                $componentType = $config['component'] ?? $section;
                
                // Get the schema data
                $params = [
                    'name' => $section,
                    '--component' => $componentType,
                ];

                // Debug data
                if (isset($config['data'])) {
                    $this->info("Section data found. Processing...");
                    
                    // Pass data directly to section command by setting environment variables
                    foreach ($config['data'] as $key => $value) {
                        if (is_array($value)) {
                            putenv("BONSAI_DATA_{$key}=" . json_encode($value));
                            $this->info("Setting array data for {$key}");
                        } else {
                            putenv("BONSAI_DATA_{$key}={$value}");
                            $this->info("Setting string data for {$key}: {$value}");
                        }
                    }
                }

                $this->call('bonsai:section', $params);
                
                // Clear environment variables
                if (isset($config['data'])) {
                    foreach ($config['data'] as $key => $value) {
                        putenv("BONSAI_DATA_{$key}");
                    }
                }
                
                // Verify section was created
                $sectionPath = resource_path("views/bonsai/sections/{$section}.blade.php");
                if (file_exists($sectionPath)) {
                    $this->info("Section file created: {$sectionPath}");
                } else {
                    $this->error("Section file not created: {$sectionPath}");
                }
                
            } catch (\Exception $e) {
                $this->error("Failed to generate section '{$section}': " . $e->getMessage());
                $this->error($e->getTraceAsString());
            }
        }
    }

    protected function generateLayouts($layouts)
    {
        $this->info('Generating layouts...');
        foreach ($layouts as $layout => $config) {
            try {
                $params = [
                    'name' => $layout
                ];

                if (isset($config['sections'])) {
                    $params['--sections'] = implode(',', $config['sections']);
                }

                $this->call('bonsai:layout', $params);
            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate layout '{$layout}': " . $e->getMessage());
            }
        }
    }

    protected function generatePages($pages)
    {
        $this->info('Generating pages...');
        foreach ($pages as $slug => $config) {
            try {
                $title = $config['title'] ?? Str::title($slug);
                
                $params = [
                    'title' => $title,
                ];

                if (isset($config['layout'])) {
                    $params['--layout'] = $config['layout'];
                }

                if (isset($config['template'])) {
                    $params['--page-template'] = $config['template'];
                }

                // Create the page
                $result = $this->call('bonsai:page', $params);

                // Set as homepage if specified
                if (isset($config['is_homepage']) && $config['is_homepage']) {
                    $this->info("Setting '{$title}' as static homepage...");
                    
                    // Get the page ID
                    $page = get_page_by_title($title);
                    
                    if ($page) {
                        // Set "Static Page" option
                        update_option('show_on_front', 'page');
                        update_option('page_on_front', $page->ID);
                        
                        $this->info("Successfully set '{$title}' as homepage");
                    } else {
                        $this->warn("Could not find page '{$title}' to set as homepage");
                    }
                }

            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate page '{$slug}': " . $e->getMessage());
            }
        }
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
}