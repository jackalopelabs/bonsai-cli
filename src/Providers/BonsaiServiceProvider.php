<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Jackalopelabs\BonsaiCli\Commands\ComponentCommand;

class BonsaiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            ComponentCommand::class,
        ]);
    }
}