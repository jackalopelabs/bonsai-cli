<?php

namespace Jackalopelabs\BonsaiCli\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use BladeUI\Icons\Factory as IconFactory;
use BladeUI\Icons\IconsManifest;
use Jackalopelabs\BonsaiCli\Commands\BonsaiInitCommand;
use Jackalopelabs\BonsaiCli\Commands\ComponentCommand;
use Jackalopelabs\BonsaiCli\Commands\CleanupCommand;
use Jackalopelabs\BonsaiCli\Commands\LayoutCommand;
use Jackalopelabs\BonsaiCli\Commands\PageCommand;
use Jackalopelabs\BonsaiCli\Commands\SectionCommand;
use Jackalopelabs\BonsaiCli\Commands\GenerateCommand;

class BonsaiServiceProvider extends ServiceProvider
{
    public function register()
    {   
        $this->app->singleton(BonsaiInitCommand::class);
        $this->app->singleton(ComponentCommand::class);
        $this->app->singleton(GenerateCommand::class);
        $this->app->singleton(CleanupCommand::class);

        $this->app->register(BonsaiComponentServiceProvider::class);

        // Register Blade Icons
        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory) {
            $factory->add('heroicons', [
                'path' => __DIR__.'/../../vendor/blade-ui-kit/blade-heroicons/resources/svg',
                'prefix' => 'heroicon',
            ]);
        });

        $this->commands([
            BonsaiInitCommand::class,
            ComponentCommand::class,
            LayoutCommand::class,
            PageCommand::class,
            SectionCommand::class,
            GenerateCommand::class,
            CleanupCommand::class,
        ]);
    }

    public function boot()
    {
        try {
            // Register anonymous components
            Blade::component('bonsai.components.header', 'header');
            Blade::component('bonsai.components.hero', 'hero');
            Blade::component('bonsai.components.card', 'card');
            
            // Register icon components
            Blade::component('bonsai.components.icons.flowchart', 'icon-flowchart');

            // Register Blade Icons
            if (class_exists(\BladeUI\Icons\BladeIconsServiceProvider::class)) {
                $this->app->register(\BladeUI\Icons\BladeIconsServiceProvider::class);
            }
            
            if (class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
                $this->app->register(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class);
            }

        } catch (\Exception $e) {
            // Log error or handle gracefully
            \Log::error("Failed to register components: " . $e->getMessage());
        }
    }
}