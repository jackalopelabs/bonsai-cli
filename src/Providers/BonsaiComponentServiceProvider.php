<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;

class BonsaiComponentServiceProvider extends ServiceProvider
{
    public function register()
    {
        // No need for this since we're handling it in boot()
    }

    public function boot()
    {
        try {
            $componentsPath = \Roots\resource_path('views/bonsai/components');
            error_log('Registering Bonsai components from: ' . $componentsPath);
            
            if (is_dir($componentsPath)) {
                // Register anonymous components namespace
                Blade::anonymousComponentNamespace('bonsai.components', 'bonsai');
                
                // Auto-discover and register components
                $files = glob($componentsPath . '/*.blade.php');
                foreach ($files as $file) {
                    $componentName = basename($file, '.blade.php');
                    error_log("Found component: {$componentName}");
                }
                
                // Also check for nested components (like icons)
                $nestedDirs = glob($componentsPath . '/*', GLOB_ONLYDIR);
                foreach ($nestedDirs as $dir) {
                    $dirName = basename($dir);
                    $nestedFiles = glob($dir . '/*.blade.php');
                    foreach ($nestedFiles as $file) {
                        $componentName = $dirName . '.' . basename($file, '.blade.php');
                        error_log("Found nested component: {$componentName}");
                    }
                }
                
                error_log('Successfully registered Bonsai components');
            }
        } catch (\Exception $e) {
            error_log("Error registering components: " . $e->getMessage());
            error_log($e->getTraceAsString());
        }
    }
} 