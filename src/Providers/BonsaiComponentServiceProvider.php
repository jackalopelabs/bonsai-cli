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
        // Register the Bonsai components directory
        Blade::loadComponentsAs('', resource_path('views/bonsai/components'));
    }
} 