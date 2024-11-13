<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class SectionCommand extends Command
{
    protected $signature = 'bonsai:section {name}';
    protected $description = 'Create a new section with custom data and page-specific logic';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = strtolower($this->argument('name'));
        $sectionPath = resource_path("views/bonsai/sections/{$name}.blade.php");

        // Ensure the sections directory exists
        $this->files->ensureDirectoryExists(resource_path('views/bonsai/sections'));

        if ($this->files->exists($sectionPath)) {
            $this->error("Section {$name} already exists at {$sectionPath}");
            return;
        }

        // Gather default data values with prompts for custom values
        $data = $this->gatherSectionData();

        // Gather page-specific conditions
        $pageConditions = $this->gatherPageConditions();

        // Write the final section file content
        $stubContent = $this->getSectionStubContent($name, $data, $pageConditions);
        $this->files->put($sectionPath, $stubContent);

        $this->info("Section {$name} created at {$sectionPath}");
    }

    protected function gatherSectionData()
    {
        // Set default values for section data
        $data = [
            'title' => 'Default Title',
            'subtitle' => 'Default subtitle',
            'l1' => 'Default feature 1',
            'l2' => 'Default feature 2',
            'l3' => 'Default feature 3',
            'l4' => 'Default feature 4',
            'primaryText' => 'Default primary text',
            'primaryLink' => '#primary-link',
            'secondaryText' => 'Default secondary text',
            'secondaryLink' => '#secondary-link',
            'imagePath' => 'images/default-hero.png',
        ];

        // Prompt the user to override each default value
        foreach ($data as $key => $default) {
            $data[$key] = $this->ask("Enter value for {$key} (default: {$default})", $default);
        }

        return $data;
    }

    protected function gatherPageConditions()
    {
        $pageConditions = [];

        // Ask if the user wants to add page-specific conditions
        if ($this->confirm('Do you want to add specific conditions for certain pages?', true)) {
            while (true) {
                $pagePath = $this->ask("Enter page path (e.g., /cypress) or press ENTER to finish");
                if (empty($pagePath)) {
                    break;
                }

                // Gather custom values for this page condition
                $pageData = $this->gatherSectionData();
                $pageConditions[$pagePath] = $pageData;
            }
        }

        return $pageConditions;
    }

    protected function getSectionStubContent($name, $data, $pageConditions)
    {
        // Default hero component content
        $defaultContent = $this->formatHeroComponent($data);

        // Start building the Blade content with conditions
        $content = <<<BLADE
{{-- Section: {$name} --}}

@if (Request::is('/'))
    {{-- Default hero section for the homepage --}}
    {$defaultContent}
BLADE;

        foreach ($pageConditions as $path => $pageData) {
            $pageContent = $this->formatHeroComponent($pageData);
            $content .= <<<BLADE

@elseif (Request::is('{$path}'))
    {{-- Hero section for the {$path} page --}}
    {$pageContent}
BLADE;
        }

        // Add a final else condition for fallback, with a single @endif at the end
        $content .= <<<BLADE

@else
    {{-- Default hero section for other pages --}}
    {$defaultContent}
@endif
BLADE;

        return $content;
    }

    protected function formatHeroComponent($data)
    {
        // Generate the <x-hero> component with data
        return <<<BLADE
<x-hero 
    title="{$data['title']}"
    subtitle="{$data['subtitle']}"
    l1="{$data['l1']}"
    l2="{$data['l2']}"
    l3="{$data['l3']}"
    l4="{$data['l4']}"
    primaryText="{$data['primaryText']}"
    primaryLink="{$data['primaryLink']}"
    secondaryText="{$data['secondaryText']}"
    secondaryLink="{$data['secondaryLink']}"
    imagePath="{$data['imagePath']}"
/>
BLADE;
    }
}
