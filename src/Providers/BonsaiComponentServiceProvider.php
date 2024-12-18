<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;

class BonsaiComponentServiceProvider extends ServiceProvider
{
    public function register()
    {
        // We have moved all component registration logic to BonsaiServiceProvider.
        // Leaving this empty or for future use.
        error_log('BonsaiComponentServiceProvider register() called');
    }

    public function boot()
    {
        // No component registration here now.
        // This provider remains as a placeholder or can be removed entirely.
        error_log('BonsaiComponentServiceProvider boot() called');
    }
}
