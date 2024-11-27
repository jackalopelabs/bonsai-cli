<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;

class BonsaiComponentServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the Bonsai component namespace
        $this->app->afterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $blade->componentNamespace('App\\View\\Components\\Bonsai', 'bonsai');
        });
    }

    public function boot()
    {
        try {
            $componentsPath = resource_path('views/bonsai/components');
            
            if (is_dir($componentsPath)) {
                // Register both namespaced and aliased components
                Blade::componentNamespace('App\\View\\Components\\Bonsai', 'bonsai');
                
                // Register view components with aliases
                $components = [
                    'hero' => 'bonsai-hero',
                    'cta' => 'bonsai-cta',
                    'widget' => 'bonsai-widget',
                    // Add other components as needed
                ];

                foreach ($components as $name => $alias) {
                    Blade::component("bonsai.components.{$name}", $alias);
                }
                
                \Log::info('Successfully registered Bonsai components');
            }
        } catch (\Exception $e) {
            \Log::error("Error registering components: " . $e->getMessage());
        }
    }
} 