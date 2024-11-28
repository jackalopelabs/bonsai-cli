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
    public function register()
    {   
        $this->app->singleton(BonsaiInitCommand::class);
        $this->app->singleton(ComponentCommand::class);
        $this->app->singleton(GenerateCommand::class);
        $this->app->singleton(CleanupCommand::class);

        $this->app->register(BonsaiComponentServiceProvider::class);

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

    public function boot()
    {
        if (!class_exists('\App\View\Components\Card')) {
            return;
        }

        // Register components only if the component class exists
        try {
            Blade::component('card', \App\View\Components\Card::class);
        } catch (\Exception $e) {
            // Log error or handle gracefully
            \Log::error("Failed to register card component: " . $e->getMessage());
        }
    }
}