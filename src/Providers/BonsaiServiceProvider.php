<?php

namespace JackalopeLabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use JackalopeLabs\BonsaiCli\Commands\BonsaiInitCommand;
use JackalopeLabs\BonsaiCli\Commands\ComponentCommand;
use JackalopeLabs\BonsaiCli\Commands\LayoutCommand;
use JackalopeLabs\BonsaiCli\Commands\PageCommand;
use JackalopeLabs\BonsaiCli\Commands\SectionCommand;
use JackalopeLabs\BonsaiCli\Commands\GenerateCommand;

class BonsaiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ComponentCommand::class);
        $this->app->singleton(BonsaiInitCommand::class);
        $this->app->singleton(GenerateCommand::class);

        $this->commands([
            BonsaiInitCommand::class,
            ComponentCommand::class,
            LayoutCommand::class,
            PageCommand::class,
            SectionCommand::class,
            GenerateCommand::class,
        ]);
    }
}