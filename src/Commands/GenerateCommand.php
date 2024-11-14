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
        $configPath = $this->option('config') ?? $this->getDefaultConfigPath($template);

        try {
            $config = $this->loadConfig($configPath);
            
            $this->info("Starting Bonsai site generation for template: {$template}");
            
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

    protected function generateComponents($components)
    {
        $this->info('Generating components...');
        foreach ($components as $component => $config) {
            $this->call('bonsai:component', [
                'name' => $component
            ]);
        }
    }

    protected function generateSections($sections)
    {
        $this->info('Generating sections...');
        foreach ($sections as $section => $config) {
            $params = [
                'name' => $section
            ];

            if (isset($config['component'])) {
                $params['--component'] = $config['component'];
            }

            $this->call('bonsai:section', $params);
        }
    }

    protected function generateLayouts($layouts)
    {
        $this->info('Generating layouts...');
        foreach ($layouts as $layout => $config) {
            $params = [
                'name' => $layout
            ];

            if (isset($config['sections'])) {
                $params['--sections'] = implode(',', $config['sections']);
            }

            $this->call('bonsai:layout', $params);
        }
    }

    protected function generatePages($pages)
    {
        $this->info('Generating pages...');
        foreach ($pages as $page => $config) {
            $params = explode(' ', $config['title'] ?? Str::title($page));
            
            if (isset($config['layout'])) {
                $params['--layout'] = $config['layout'];
            }

            $this->call('bonsai:page', $params);
        }
    }

    protected function generateDatabase($database)
    {
        if (empty($database)) {
            return;
        }

        $this->info('Configuring database...');
        
        // Handle different types of database operations
        if (!empty($database['seeds'])) {
            foreach ($database['seeds'] as $seeder) {
                $this->call('db:seed', ['--class' => $seeder]);
            }
        }

        if (!empty($database['imports'])) {
            foreach ($database['imports'] as $import) {
                // Handle SQL imports
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
        $envContent = file_get_contents($envPath);

        foreach ($envVars as $key => $value) {
            // Update existing vars or add new ones
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
            // Store API keys securely based on your preferred method
            // This could be in the database, .env file, or other secure storage
        }
    }

    protected function importSqlFile($sqlFile)
    {
        // Implement SQL file import logic
    }

    protected function getDefaultConfigPath($template)
    {
        return __DIR__ . "/../../config/templates/{$template}.yml";
    }

    protected function loadConfig($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("Configuration file not found: {$path}");
        }

        return Yaml::parseFile($path);
    }
}