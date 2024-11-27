<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class LayoutCommand extends Command
{
    protected $signature = 'bonsai:layout {name} {--sections=}';
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

        // Ensure the layouts directory exists
        $this->files->ensureDirectoryExists(resource_path('views/bonsai/layouts'));

        // Create app.blade.php if it doesn't exist
        $this->ensureBonsaiAppLayoutExists();

        if ($this->files->exists($layoutPath)) {
            $this->error("Layout {$name} already exists at {$layoutPath}");
            return;
        }

        // Get sections to include in the layout
        $sections = $this->getSections();

        // Generate layout content with @include directives for each section
        $stubContent = $this->getLayoutStubContent($name, $sections);
        $this->files->put($layoutPath, $stubContent);

        $this->info("Layout {$name} created at {$layoutPath}");
    }

    protected function ensureBonsaiAppLayoutExists()
    {
        $appLayoutPath = resource_path('views/bonsai/layouts/app.blade.php');
        
        if (!$this->files->exists($appLayoutPath)) {
            $appLayoutContent = $this->getBonsaiAppLayoutContent();
            $this->files->put($appLayoutPath, $appLayoutContent);
            $this->info('Created Bonsai app layout at ' . $appLayoutPath);
        }
    }

    protected function getBonsaiAppLayoutContent()
    {
        return <<<'BLADE'
<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @include('utils.styles')
    </head>

    <body @php(body_class('h-full bg-gray-100'))>
        @php(wp_body_open())

        <div id="app">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>

            @include('sections.header')

            <main id="main" class="max-w-5xl mx-auto">
                <div class="{{ $containerInnerClasses ?? '' }}">
                    @yield('content')
                </div>
            </main>

            @include('sections.footer')
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
        // Check if sections were provided as an option
        $sections = $this->option('sections') ? explode(',', $this->option('sections')) : [];

        // If not, prompt for sections interactively
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

    protected function getLayoutStubContent($name, $sections)
    {
        $sectionIncludes = collect($sections)
            ->map(fn($section) => "@include('bonsai.sections.{$section}')")
            ->implode("\n        ");

        return <<<BLADE
{{-- Layout: {$name} --}}
@extends('bonsai.layouts.app')

@section('content')
    <div class="layout-{$name}">
        {{-- Include sections in specified order --}}
        {$sectionIncludes}
        {{-- Add other sections as needed --}}
    </div>
@endsection
BLADE;
    }
}