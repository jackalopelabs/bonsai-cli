<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Jackalopelabs\BonsaiCli\Commands\BonsaiInitCommand;
use Jackalopelabs\BonsaiCli\Commands\ComponentCommand;
use Jackalopelabs\BonsaiCli\Commands\CleanupCommand;
use Jackalopelabs\BonsaiCli\Commands\LayoutCommand;
use Jackalopelabs\BonsaiCli\Commands\PageCommand;
use Jackalopelabs\BonsaiCli\Commands\SectionCommand;
use Jackalopelabs\BonsaiCli\Commands\GenerateCommand;

class BonsaiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {   
        // Register commands
        $this->commands([
            BonsaiInitCommand::class,
            ComponentCommand::class,
            LayoutCommand::class,
            PageCommand::class,
            SectionCommand::class,
            GenerateCommand::class,
            CleanupCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        try {
            // Register anonymous components
            Blade::component('bonsai.components.header', 'header');
            Blade::component('bonsai.components.hero', 'hero');
            Blade::component('bonsai.components.card', 'card');
            
            // Register icon components
            Blade::component('bonsai.components.icons.flowchart', 'icon-flowchart');

        } catch (\Exception $e) {
            // Log error or handle gracefully
            \Log::error("Failed to register components: " . $e->getMessage());
        }
    }
}