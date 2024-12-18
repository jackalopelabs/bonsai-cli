<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;

class BonsaiComponentServiceProvider extends ServiceProvider
{
    public function register()
    {
        error_log('BonsaiComponentServiceProvider register() called');
    }

    public function boot()
    {
        error_log('BonsaiComponentServiceProvider boot() called');
        // All component logic is now in BonsaiServiceProvider.
        // This provider can remain empty or be removed entirely.
    }
}
