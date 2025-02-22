<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register Heroicons
        foreach (glob(resource_path('views/components/icons/*.blade.php')) as $icon) {
            $iconName = basename($icon, '.blade.php');
            Blade::component('components.icons.' . $iconName, $iconName);
        }

        // Register Heroicons
        foreach (glob(resource_path('views/components/icons/*.blade.php')) as $icon) {
            $iconName = basename($icon, '.blade.php');
            Blade::component('components.icons.' . $iconName, $iconName);
        }

        // Register Heroicons
        foreach (glob(resource_path('views/components/icons/*.blade.php')) as $icon) {
            $iconName = basename($icon, '.blade.php');
            Blade::component('components.icons.' . $iconName, $iconName);
        }

        // Register Heroicons
        foreach (glob(resource_path('views/components/icons/*.blade.php')) as $icon) {
            $iconName = basename($icon, '.blade.php');
            Blade::component('components.icons.' . $iconName, $iconName);
        }

        // Register Heroicons
        foreach (glob(resource_path('views/components/icons/*.blade.php')) as $icon) {
            $iconName = basename($icon, '.blade.php');
            Blade::component('components.icons.' . $iconName, $iconName);
        }

        // Register Heroicons
        foreach (glob(resource_path('views/components/icons/*.blade.php')) as $icon) {
            $iconName = basename($icon, '.blade.php');
            Blade::component('components.icons.' . $iconName, $iconName);
        }

        // Component registrations will be added here
    }
}