<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BonsaiServiceProvider extends ServiceProvider
{
    public function register()
    {   
        // Register commands
        $this->commands([
            \Jackalopelabs\BonsaiCli\Commands\BonsaiInitCommand::class,
            \Jackalopelabs\BonsaiCli\Commands\ComponentCommand::class,
            \Jackalopelabs\BonsaiCli\Commands\LayoutCommand::class,
            \Jackalopelabs\BonsaiCli\Commands\PageCommand::class,
            \Jackalopelabs\BonsaiCli\Commands\SectionCommand::class,
            \Jackalopelabs\BonsaiCli\Commands\GenerateCommand::class,
            \Jackalopelabs\BonsaiCli\Commands\CleanupCommand::class,
        ]);
    }

    public function boot()
    {
        // Register Blade components
        $this->registerBladeComponents();
        
        // Register Bonsai view namespace
        $this->app['view']->addNamespace('bonsai', resource_path('views/bonsai'));
        
        // Add template path filter with debug info
        add_filter('template_include', function($template) {
            $template_slug = get_page_template_slug();
            
            if (!$template_slug) {
                return $template;
            }

            // Debug info
            error_log("Template slug: " . $template_slug);

            // First check in resources/views
            $resource_path = resource_path("views/bonsai/templates/{$template_slug}");
            if (file_exists($resource_path)) {
                error_log("Found template in resources: " . $resource_path);
                return $resource_path;
            }

            // Then check in theme directory
            $theme_path = get_theme_file_path("views/bonsai/templates/{$template_slug}");
            if (file_exists($theme_path)) {
                error_log("Found template in theme: " . $theme_path);
                return $theme_path;
            }

            error_log("No template found, using default: " . $template);
            return $template;
        });

        // Register templates with WordPress
        add_action('theme_page_templates', function($page_templates) {
            // Get all template files in the bonsai templates directory
            $bonsai_templates = [];
            $template_dir = resource_path('views/bonsai/templates');
            
            if (is_dir($template_dir)) {
                $files = glob($template_dir . '/template-*.blade.php');
                foreach ($files as $file) {
                    $basename = basename($file);
                    // Get template name from file header
                    $contents = file_get_contents($file);
                    if (preg_match('/Template Name:\s*(.+)$/m', $contents, $matches)) {
                        $bonsai_templates[$basename] = trim($matches[1]);
                    }
                }
            }

            return array_merge($page_templates, $bonsai_templates);
        });

        // Add view composer for layout variables
        view()->composer('bonsai.layouts.bonsai', function ($view) {
            $view->with([
                'containerInnerClasses' => 'px-6',
            ]);
        });
    }

    protected function registerBladeComponents()
    {
        try {
            // Register anonymous components
            Blade::component('bonsai.components.header', 'header');
            Blade::component('bonsai.components.hero', 'hero');
            Blade::component('bonsai.components.card', 'card');
            Blade::component('bonsai.components.widget', 'widget');
            Blade::component('bonsai.components.accordion', 'accordion');
            Blade::component('bonsai.components.cta', 'cta');
            Blade::component('bonsai.components.list-item', 'list-item');
            Blade::component('bonsai.components.pricing-box', 'pricing-box');
            
            // Register icon components
            Blade::component('bonsai.components.icons.flowchart', 'icon-flowchart');
        } catch (\Exception $e) {
            \Log::error("Failed to register components: " . $e->getMessage());
        }
    }
}