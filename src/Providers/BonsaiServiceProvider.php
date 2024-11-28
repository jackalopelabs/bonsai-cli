<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Jackalopelabs\BonsaiCli\Commands\BonsaiInitCommand;
use Jackalopelabs\BonsaiCli\Commands\ComponentCommand;
use Jackalopelabs\BonsaiCli\Commands\CleanupCommand;
use Jackalopelabs\BonsaiCli\Commands\LayoutCommand;
use Jackalopelabs\BonsaiCli\Commands\PageCommand;
use Jackalopelabs\BonsaiCli\Commands\SectionCommand;
use Jackalopelabs\BonsaiCli\Commands\GenerateCommand;
use Jackalopelabs\BonsaiCli\Commands\BonsaiTreeCommand;
use Jackalopelabs\BonsaiCli\Commands\BonsaiArtCommand;

class BonsaiServiceProvider extends ServiceProvider
{
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
            BonsaiTreeCommand::class,
            BonsaiArtCommand::class,
        ]);

        // Register config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/bonsai.php', 'bonsai'
        );
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../../config/bonsai.php' => config_path('bonsai.php'),
        ], 'bonsai-config');
    }
}