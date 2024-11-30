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
        
        // Register templates with WordPress
        add_action('theme_page_templates', function($page_templates) {
            return array_merge($page_templates, [
                'template-components.blade.php' => 'Components Library',
                'template-bonsai.blade.php' => 'Bonsai Template',
                'template-cypress.blade.php' => 'Cypress Template',
            ]);
        });

        // Add template path filter
        add_filter('template_include', function($template) {
            $template_slug = get_page_template_slug();
            
            if (!$template_slug) {
                return $template;
            }

            // Check if it's a Bonsai template
            if (strpos($template_slug, 'template-') === 0) {
                $view_path = 'views/bonsai/templates/' . $template_slug;
                if (file_exists(get_theme_file_path($view_path))) {
                    return get_theme_file_path($view_path);
                }
            }

            return $template;
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