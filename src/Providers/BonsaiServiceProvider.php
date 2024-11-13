<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Jackalopelabs\BonsaiCli\Commands\ComponentCommand;
use Jackalopelabs\BonsaiCli\Commands\BonsaiInitCommand;

class BonsaiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ComponentCommand::class);
        $this->app->singleton(BonsaiInitCommand::class);

        $this->commands([
            ComponentCommand::class,
            BonsaiInitCommand::class,
        ]);
    }
}
