<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BonsaiServiceProvider extends ServiceProvider
{
    public function register()
    {
        error_log('BonsaiServiceProvider register() method called');

        // Register commands only if running in console
        if ($this->app->runningInConsole()) {
            error_log('Registering Bonsai commands...');
            $this->commands([
                \Jackalopelabs\BonsaiCli\Commands\BonsaiInitCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\ComponentCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\LayoutCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\PageCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\SectionCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\GenerateCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\CleanupCommand::class,
            ]);
            error_log('Bonsai commands registered');
        }
    }

    public function boot()
    {
        error_log('BonsaiServiceProvider boot() method called');

        // Register Bonsai view namespace
        $this->app['view']->addNamespace('bonsai', resource_path('views/bonsai'));

        // Register Blade components
        $this->registerBladeComponents();

        // Load theme settings
        $this->loadThemeSettings();

        // Template path filter
        add_filter('template_include', function($template) {
            $template_slug = get_page_template_slug();
            if (!$template_slug) {
                return $template;
            }

            error_log("Template slug: {$template_slug}");

            $resource_path = resource_path("views/bonsai/templates/{$template_slug}");
            if (file_exists($resource_path)) {
                error_log("Found template in resources: {$resource_path}");
                return $resource_path;
            }

            $theme_path = get_theme_file_path("views/bonsai/templates/{$template_slug}");
            if (file_exists($theme_path)) {
                error_log("Found template in theme: {$theme_path}");
                return $theme_path;
            }

            error_log("No template found, using default: {$template}");
            return $template;
        });

        // Register templates with WordPress
        add_action('theme_page_templates', function($page_templates) {
            $bonsai_templates = [];
            $template_dir = resource_path('views/bonsai/templates');

            if (is_dir($template_dir)) {
                $files = glob($template_dir . '/template-*.blade.php');
                foreach ($files as $file) {
                    $basename = basename($file);
                    $contents = file_get_contents($file);
                    if (preg_match('/Template Name:\s*(.+)$/m', $contents, $matches)) {
                        $bonsai_templates[$basename] = trim($matches[1]);
                    }
                }
            }

            return array_merge($page_templates, $bonsai_templates);
        });

        // Example view composer
        view()->composer('bonsai.layouts.bonsai', function ($view) {
            $view->with(['containerInnerClasses' => 'px-6']);
        });
    }

    protected function loadThemeSettings()
    {
        $template = 'bonsai'; // Default template
        $configPaths = [
            base_path("config/bonsai/templates/{$template}.yml"),
            base_path("config/bonsai/{$template}.yml"),
            base_path("config/templates/{$template}.yml"),
            __DIR__ . "/../../config/templates/{$template}.yml"
        ];

        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $config = \Symfony\Component\Yaml\Yaml::parseFile($path);
                $themeSettings = $config['theme'] ?? [
                    'body' => ['class' => 'bg-gray-100']
                ];

                // Share theme settings with all views
                view()->share('themeSettings', $themeSettings);

                // Add body class filter
                add_filter('body_class', function($classes) use ($themeSettings) {
                    $bodyClass = $themeSettings['body']['class'] ?? 'bg-gray-100';
                    $classes[] = $bodyClass;
                    return $classes;
                });

                break;
            }
        }
    }

    protected function registerBladeComponents()
    {
        error_log('registerBladeComponents called in BonsaiServiceProvider');

        // Register an anonymous namespace for bonsai components
        Blade::anonymousComponentNamespace('bonsai.components', 'bonsai');

        $componentsPath = resource_path('views/bonsai/components');
        if (!is_dir($componentsPath)) {
            error_log('No bonsai components directory found at: ' . $componentsPath);
            return;
        }

        // Register main level components
        $files = glob($componentsPath . '/*.blade.php');
        foreach ($files as $file) {
            $componentName = basename($file, '.blade.php');
            Blade::component("bonsai.components.{$componentName}", "bonsai::{$componentName}");
            error_log("Registered component: {$componentName} as <x-bonsai::{$componentName}>");
        }

        // Register nested components (e.g., icons)
        $nestedDirs = glob($componentsPath . '/*', GLOB_ONLYDIR);
        foreach ($nestedDirs as $dir) {
            $dirName = basename($dir);
            $nestedFiles = glob($dir . '/*.blade.php');
            foreach ($nestedFiles as $nestedFile) {
                $nestedComponentName = basename($nestedFile, '.blade.php');
                $fullName = "{$dirName}.{$nestedComponentName}";
                Blade::component("bonsai.components.{$fullName}", "bonsai::{$fullName}");
                error_log("Registered nested component: {$fullName} as <x-bonsai::{$fullName}>");
            }
        }

        error_log('Finished registering bonsai components.');
    }
}
