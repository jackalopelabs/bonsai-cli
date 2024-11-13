<?php

namespace Bonsai\Providers;

use Illuminate\Support\ServiceProvider;
use Bonsai\Commands\ComponentCommand;

class BonsaiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ComponentCommand::class);
        $this->commands([
            ComponentCommand::class,
        ]);
    }
}
