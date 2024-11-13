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
@extends('layouts.app')

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
