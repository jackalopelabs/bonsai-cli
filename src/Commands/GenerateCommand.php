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
        foreach ($components as $component => $config) {
            try {
                $this->call('bonsai:component', [
                    'name' => is_array($config) ? $component : $config
                ]);
            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate component '{$component}': " . $e->getMessage());
            }
        }
    }

    protected function generateSections($sections)
    {
        $this->info('Generating sections...');
        foreach ($sections as $section => $config) {
            try {
                $this->info("Creating section: {$section}");
                $this->info("Component: " . ($config['component'] ?? $section));
                
                $params = [
                    'name' => $section,
                    '--component' => $config['component'] ?? $section
                ];

                // Debug data
                if (isset($config['data'])) {
                    $this->info("Section data: " . json_encode($config['data'], JSON_PRETTY_PRINT));
                }

                $this->call('bonsai:section', $params);
                
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
        foreach ($pages as $page => $config) {
            try {
                $params = [
                    'title' => $config['title'] ?? Str::title($page)
                ];

                if (isset($config['layout'])) {
                    $params['--layout'] = $config['layout'];
                }

                $this->call('bonsai:page', $params);
            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate page '{$page}': " . $e->getMessage());
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
                $this->call('db:seed', ['--class' => $seeder]);
            }
        }

        if (!empty($database['imports'])) {
            foreach ($database['imports'] as $import) {
                if (str_ends_with($import, '.sql')) {
                    $this->importSqlFile($import);
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

        // WordPress options
        foreach ($settings['options'] ?? [] as $option => $value) {
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
            // Here you could implement secure storage of API keys
            // For now, we'll just store them in .env
            $this->updateEnvFile($keys);
        }
    }

    protected function importSqlFile($sqlFile)
    {
        // Implement SQL import logic if needed
    }
}