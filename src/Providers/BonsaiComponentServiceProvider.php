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
        // Debug info
        \Log::info('Booting BonsaiComponentServiceProvider');
        
        try {
            // Register the Bonsai components directory
            $componentsPath = resource_path('views/bonsai/components');
            \Log::info("Components path: {$componentsPath}");
            
            if (is_dir($componentsPath)) {
                Blade::loadComponentsAs('bonsai', $componentsPath);
                \Log::info('Successfully registered Bonsai components');
            } else {
                \Log::warning("Components directory not found: {$componentsPath}");
            }
        } catch (\Exception $e) {
            \Log::error("Error registering components: " . $e->getMessage());
        }
    }
} 