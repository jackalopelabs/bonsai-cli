<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BonsaiComponentServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Blade::componentNamespace('Jackalopelabs\\BonsaiCli\\View\\Components', 'bonsai');

        // Register Blade components
        Blade::component('bonsai::components.hero', 'bonsai-hero');
        Blade::component('bonsai::components.header', 'bonsai-header');
        Blade::component('bonsai::components.card', 'bonsai-card');
        Blade::component('bonsai::components.widget', 'bonsai-widget');
        Blade::component('bonsai::components.pricing-box', 'bonsai-pricing-box');
        Blade::component('bonsai::components.feature-grid', 'bonsai-feature-grid');
        
        // Register sub-components
        Blade::component('bonsai::components.accordion', 'bonsai-accordion');
        Blade::component('bonsai::components.cta', 'bonsai-cta');
        Blade::component('bonsai::components.list-item', 'bonsai-list-item');
    }
}
