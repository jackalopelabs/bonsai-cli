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
                // Register base component namespace
                Blade::componentNamespace('App\\View\\Components\\Bonsai', 'bonsai');
                
                // Auto-discover and register components
                $files = glob($componentsPath . '/*.blade.php');
                foreach ($files as $file) {
                    $componentName = basename($file, '.blade.php');
                    Blade::component("bonsai.components.{$componentName}", "bonsai-{$componentName}");
                }
                
                \Log::info('Successfully registered Bonsai components');
            }
        } catch (\Exception $e) {
            \Log::error("Error registering components: " . $e->getMessage());
        }
    }
} 