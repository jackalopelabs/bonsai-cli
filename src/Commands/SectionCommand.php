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

        $this->files->ensureDirectoryExists(resource_path('views/bonsai/sections'));

        if ($this->files->exists($sectionPath)) {
            $this->error("Section {$name} already exists at {$sectionPath}");
            return;
        }

        $data = $this->gatherSectionData($name);
        $pageConditions = $this->gatherPageConditions();

        $stubContent = $this->getSectionStubContent($name, $data, $pageConditions);
        $this->files->put($sectionPath, $stubContent);

        $this->info("Section {$name} created at {$sectionPath}");
    }

    protected function gatherSectionData($componentType)
    {
        $defaultProps = $this->getComponentProps($componentType);
        foreach ($defaultProps as $key => $default) {
            $defaultProps[$key] = $this->ask("Enter value for {$key} (default: {$default})", $default);
        }
        return $defaultProps;
    }

    protected function gatherPageConditions()
    {
        $pageConditions = [];

        if ($this->confirm('Do you want to add specific conditions for certain pages?', true)) {
            while (true) {
                $pagePath = $this->ask("Enter page path (e.g., /cypress) or press ENTER to finish");
                if (empty($pagePath)) {
                    break;
                }
                $pageData = $this->gatherSectionData('hero');
                $pageConditions[$pagePath] = $pageData;
            }
        }

        return $pageConditions;
    }

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
    
            // Gather default data values for the component
            $data = $this->getComponentProps($name);
    
            // Define page-specific conditions if needed
            $pageConditions = $this->gatherPageConditions();
    
            // Generate and write section file content
            $stubContent = $this->getSectionStubContent($name, $data, $pageConditions);
            $this->files->put($sectionPath, $stubContent);
    
            $this->info("Section {$name} created at {$sectionPath}");
        }
    
        protected function getSectionStubContent($name, $data, $pageConditions)
        {
            $defaultContent = $this->formatComponent($name, $data);
    
            // Start Blade content with default content and conditions
            $content = <<<BLADE
    {{-- Section: {$name} --}}
    
    @if (Request::is('/'))
        {{-- Default section content for the homepage --}}
        {$defaultContent}
    @else
        {{-- Default section content for other pages --}}
        {$defaultContent}
    @endif
    BLADE;
    
            foreach ($pageConditions as $path => $pageData) {
                $pageContent = $this->formatComponent($name, $pageData);
                $content = str_replace(
                    "@else",
                    "@elseif (Request::is('{$path}'))\n    {{-- Section content for {$path} page --}}\n    {$pageContent}\n@else",
                    $content
                );
            }
    
            return $content;
        }
    
        protected function formatComponent($name, $data)
        {
            // If the component has array data, use :attribute syntax
            $props = collect($data)->map(function ($value, $key) {
                return is_array($value)
                    ? ":{$key}=" . json_encode($value)
                    : "{$key}=\"{$value}\"";
            })->implode(' ');
    
            return "<x-{$name} {$props} />";
        }
    
        protected function getComponentProps($componentType)
        {
            // Default component properties for each component type
            $components = [
                'accordion' => [
                    'item' => ["id" => 'example', 'title' => 'Accordion Title', 'content' => 'Accordion content here'],
                    'open' => false,
                ],
                'alert' => [
                    'type' => 'success',
                    'message' => 'Alert message here',
                ],
                'button' => [
                    'variant' => 'primary',
                    'size' => 'base',
                    'element' => 'button',
                ],
                'faq' => [
                    'faqs' => [['question' => 'Sample question?', 'answer' => 'Sample answer']],
                ],
                'hero' => [
                    'title' => 'Sample Title',
                    'subtitle' => 'Sample subtitle',
                    'l1' => 'Feature 1',
                    'l2' => 'Feature 2',
                    'l3' => 'Feature 3',
                    'l4' => 'Feature 4',
                    'primaryText' => 'Primary CTA',
                    'primaryLink' => '#',
                    'secondaryText' => 'Secondary CTA',
                    'secondaryLink' => '#',
                    'imagePath' => 'images/sample-hero.png',
                ],
                'modal' => [
                    'title' => 'Modal Title',
                ],
                'table' => [
                    'rows' => [['Column 1', 'Column 2', 'Column 3']],
                    'columns' => ['Column 1', 'Column 2', 'Column 3'],
                ],
            ];
    
            return $components[$componentType] ?? [];
        }
    
        protected function gatherPageConditions()
        {
            $pageConditions = [];
    
            // Ask if the user wants to add page-specific conditions
            if ($this->confirm('Do you want to add specific conditions for certain pages?', true)) {
                while (true) {
                    $pagePath = $this->ask("Enter page path (e.g., /example-page) or press ENTER to finish");
                    if (empty($pagePath)) {
                        break;
                    }
    
                    // Gather custom values for this page condition
                    $pageData = $this->getComponentProps($this->argument('name'));
                    $pageConditions[$pagePath] = $pageData;
                }
            }
    
            return $pageConditions;
        }
    }
    