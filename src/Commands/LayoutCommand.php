<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class LayoutCommand extends Command
{
    protected $signature = 'bonsai:layout {name} {--sections=} {--template=}';
    protected $description = 'Create a new layout with specified sections';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = strtolower($this->argument('name'));
        $layoutPath = resource_path("views/bonsai/layouts/{$name}.blade.php");
        $template = $this->option('template') ?? 'bonsai';

        // Ensure the layouts directory exists
        $this->files->ensureDirectoryExists(resource_path('views/bonsai/layouts'));

        if ($this->files->exists($layoutPath)) {
            $this->error("Layout {$name} already exists at {$layoutPath}");
            return;
        }

        // Get theme settings from template configuration
        $themeSettings = $this->getThemeSettings($template);

        // Get sections to include in the layout
        $sections = $this->getSections();

        // Generate layout content with theme settings
        $stubContent = $this->getLayoutContent($name, $sections, $themeSettings);

        $this->files->put($layoutPath, $stubContent);
        $this->info("Layout {$name} created at {$layoutPath}");
    }

    protected function getThemeSettings($template)
    {
        // Try to load template configuration
        $configPaths = [
            base_path("config/bonsai/{$template}.yml"),
            base_path("config/bonsai/templates/{$template}.yml"),
            base_path("config/templates/{$template}.yml"),
            __DIR__ . "/../../config/templates/{$template}.yml"
        ];

        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $config = Yaml::parseFile($path);
                return $config['theme'] ?? [
                    'body' => ['class' => 'bg-gray-100'],
                    'header' => ['class' => 'bg-opacity-60 backdrop-blur-md shadow-lg border border-transparent rounded-full mx-auto p-1 my-4']
                ];
            }
        }

        // Return defaults if no config found
        return [
            'body' => ['class' => 'bg-gray-100'],
            'header' => ['class' => 'bg-opacity-60 backdrop-blur-md shadow-lg border border-transparent rounded-full mx-auto p-1 my-4']
        ];
    }

    protected function getLayoutContent($name, $sections, $themeSettings)
    {
        if ($name === 'cypress') {
            return $this->getCypressLayoutContent($themeSettings);
        }

        return $this->getDefaultLayoutContent($name, $sections, $themeSettings);
    }

    protected function getDefaultLayoutContent($name, $sections, $themeSettings)
    {
        $sectionIncludes = collect($sections)
            ->map(fn($section) => "@include('bonsai.sections.{$section}')")
            ->implode("\n        ");

        $bodyClass = $themeSettings['body']['class'] ?? 'bg-gray-100';

        return <<<BLADE
{{-- Layout: {$name} --}}
<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @include('utils.styles')
    </head>

    <body @php(body_class("{$bodyClass}"))>
        @php(wp_body_open())
        @php(\$containerInnerClasses = 'container mx-auto px-4 py-8')

        <div id="app">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>

            @includeIf('bonsai.sections.header')

            <main id="main" class="main-content">
                @hasSection('header')
                    @yield('header')
                @endif

                <div class="{{ \$containerInnerClasses }}">
                    @yield('content')
                </div>

                @hasSection('footer')
                    @yield('footer')
                @endif
            </main>

            @includeIf('bonsai.sections.footer')
        </div>

        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html>
BLADE;
    }

    protected function getCypressLayoutContent($themeSettings)
    {
        $bodyClass = $themeSettings['body']['class'] ?? 'bg-gray-100';

        return <<<BLADE
<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @include('utils.styles')
    </head>

    <body @php(body_class("{$bodyClass}"))>
        @php(wp_body_open())
        @php(\$containerInnerClasses = 'container mx-auto px-4 py-8')

        <div id="app">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>

            @includeIf('bonsai.sections.header')

            <main id="main" class="max-w-5xl mx-auto">
                <div class="{{ \$containerInnerClasses }}">
                    @yield('content')
                </div>
            </main>

            @includeIf('sections.footer')
        </div>

        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html>
BLADE;
    }

    protected function getSections()
    {
        $sections = $this->option('sections') ? explode(',', $this->option('sections')) : [];

        if (empty($sections)) {
            $this->info("Specify the sections to include in the layout (e.g., hero,about,cta)");
            while (true) {
                $section = $this->ask("Enter a section name (or press ENTER to finish)");
                if (empty($section)) {
                    break;
                }
                $sections[] = $section;
            }
        }

        return $sections;
    }
}
