<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BonsaiServiceProvider extends ServiceProvider
{
    protected function log($message)
    {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::log($message);
        }
    }

    public function register()
    {
        $this->log('BonsaiServiceProvider register() method called');

        // Register commands only if running in console
        if ($this->app->runningInConsole()) {
            $this->log('Registering Bonsai commands...');
            $this->commands([
                \Jackalopelabs\BonsaiCli\Commands\BonsaiInitCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\ComponentCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\LayoutCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\PageCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\SectionCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\GenerateCommand::class,
                \Jackalopelabs\BonsaiCli\Commands\CleanupCommand::class,
            ]);
            $this->log('Bonsai commands registered');
        }
    }

    public function boot()
    {
        $this->log('BonsaiServiceProvider boot() method called');

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

            $this->log("Template slug: {$template_slug}");

            $resource_path = resource_path("views/bonsai/templates/{$template_slug}");
            if (file_exists($resource_path)) {
                $this->log("Found template in resources: {$resource_path}");
                return $resource_path;
            }

            $theme_path = get_theme_file_path("views/bonsai/templates/{$template_slug}");
            if (file_exists($theme_path)) {
                $this->log("Found template in theme: {$theme_path}");
                return $theme_path;
            }

            $this->log("No template found, using default: {$template}");
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
                $themeSettings = $config['theme'] ?? [];

                // Share theme settings with all views
                view()->share('themeSettings', $themeSettings);

                // Add Sage body class filter
                add_filter('sage/body/classes', function($classes) use ($themeSettings) {
                    $bodyClass = $themeSettings['body']['class'] ?? 'bg-gray-100';
                    $classes[] = $bodyClass;

                    // Add background image classes if present
                    if (!empty($themeSettings['body']['background']['image'])) {
                        $classes[] = 'bg-no-repeat';  // Default
                        
                        // Add size class
                        $size = $themeSettings['body']['background']['styles']['size'] ?? 'cover';
                        $classes[] = "bg-{$size}";

                        // Add position class
                        $position = $themeSettings['body']['background']['styles']['position'] ?? 'center';
                        $classes[] = "bg-{$position}";

                        // Add repeat class if not using no-repeat
                        $repeat = $themeSettings['body']['background']['styles']['repeat'] ?? 'no-repeat';
                        if ($repeat !== 'no-repeat') {
                            $classes[] = "bg-{$repeat}";
                        }
                    }

                    return $classes;
                });

                // Add inline styles for background image
                add_action('wp_head', function() use ($themeSettings) {
                    if (!empty($themeSettings['body']['background']['image'])) {
                        $opacity = $themeSettings['body']['background']['styles']['opacity'] ?? '100';
                        $opacity = intval($opacity) / 100;
                        
                        // Get the correct image URL using Sage's asset handling
                        $imagePath = $themeSettings['body']['background']['image'];
                        if (function_exists('sage')) {
                            // If path starts with /resources/, remove it
                            $imagePath = preg_replace('/^\/resources\//', '', $imagePath);
                            $imageUrl = sage($imagePath);
                        } else {
                            // Fallback to theme directory
                            $imageUrl = get_theme_file_uri($imagePath);
                        }
                        
                        echo '<style>
                            body::before {
                                content: "";
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                z-index: -1;
                                background-image: url("' . esc_url($imageUrl) . '");
                                opacity: ' . $opacity . ';
                            }
                        </style>';
                    }
                });

                break;
            }
        }
    }

    protected function registerBladeComponents()
    {
        $this->log('🔍 Starting template-specific component registration...');

        // Register core Bonsai components
        $this->registerCoreComponents();

        // Register template-specific components
        $this->registerTemplateComponents();

        // Register Heroicons
        $this->registerHeroicons();

        $this->log('Finished registering bonsai components.');
    }

    protected function registerCoreComponents()
    {
        $this->log('Registering core Bonsai components...');

        // Register core components
        $coreComponents = [
            'accordion',
            'card',
            'cta',
            'header',
            'hero',
            'list-item',
            'pricing-box',
            'widget'
        ];

        foreach ($coreComponents as $componentName) {
            // Register in bonsai namespace only if component exists
            $componentPath = resource_path("views/bonsai/components/{$componentName}.blade.php");
            if (file_exists($componentPath)) {
                Blade::component("bonsai.components.{$componentName}", "bonsai::{$componentName}");
                $this->log("✓ Registered core component: {$componentName} as <x-bonsai::{$componentName}>");
            }
        }
    }

    protected function registerTemplateComponents()
    {
        $this->log('🔍 Starting template-specific component registration...');

        // Get all template directories
        $viewsPath = resource_path('views');
        $this->log("📂 Scanning for template directories in: {$viewsPath}/bonsai/components/*");
        
        $templateDirs = glob($viewsPath . '/bonsai/components/*', GLOB_ONLYDIR);
        $this->log("Found " . count($templateDirs) . " template directories");
        
        foreach ($templateDirs as $templateDir) {
            $templateName = basename($templateDir);
            
            // Skip non-template directories
            if (in_array($templateName, ['icons', 'utils'])) {
                $this->log("⏭️ Skipping utility directory: {$templateName}");
                continue;
            }

            $this->log("📦 Processing template: {$templateName}");

            // Register components in template directory
            $componentFiles = glob($templateDir . '/*.blade.php');
            $this->log("Found " . count($componentFiles) . " component files in {$templateName}");
            
            foreach ($componentFiles as $file) {
                $componentName = basename($file, '.blade.php');
                try {
                    // Register with template namespace
                    $templateAlias = "bonsai::{$templateName}.{$componentName}";
                    $path = "bonsai.components.{$templateName}.{$componentName}";
                    
                    Blade::component($path, $templateAlias);
                    $this->log("✓ Registered template component: {$componentName} as <x-{$templateAlias}>");
                    
                    // Also register without template namespace for backward compatibility
                    $backwardAlias = "bonsai::{$componentName}";
                    Blade::component($path, $backwardAlias);
                    $this->log("✓ Also registered as <x-{$backwardAlias}> for compatibility");
                } catch (\Exception $e) {
                    $this->log("❌ Failed to register component {$componentName}: " . $e->getMessage());
                }
            }
        }
        
        $this->log("🏁 Completed template-specific component registration");
    }

    protected function registerHeroicons()
    {
        $this->log('🔍 Starting Heroicon registration...');
        
        // Check if icons directory exists
        $iconsDir = resource_path('views/bonsai/components/icons');
        if (!is_dir($iconsDir)) {
            $this->log("Creating icons directory at: {$iconsDir}");
            mkdir($iconsDir, 0755, true);
        }

        // Register any existing icon components
        $iconFiles = glob($iconsDir . '/*.blade.php');
        foreach ($iconFiles as $file) {
            $iconName = basename($file, '.blade.php');
            $alias = "bonsai::icons.{$iconName}";
            $path = "bonsai.components.icons.{$iconName}";
            
            try {
                Blade::component($path, $alias);
                $this->log("✓ Registered icon component: {$iconName} as <x-{$alias}>");
            } catch (\Exception $e) {
                $this->log("❌ Failed to register icon {$iconName}: " . $e->getMessage());
            }
        }

        $this->log('✓ Completed Heroicon registration');
    }
}
