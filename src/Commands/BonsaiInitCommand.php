<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class BonsaiInitCommand extends Command
{
    protected $signature = 'bonsai:init';
    protected $description = 'Initialize project by creating a Components page and setting up default templates';

    protected $files;

    protected $components = [
        'slideshow' => 'Dynamic slideshow for showcasing content',
        'hero' => 'Hero section for prominent page headers',
        'faq' => 'Frequently asked questions component with expandable answers',
        'accordion' => 'Interactive accordion component for toggling content visibility',
        'alert' => 'Contextual feedback messages for typical user actions',
        'button' => 'Customizable button component with multiple variants',
        'card-component' => 'Versatile card component for displaying content',
        'card-featured' => 'Featured card with enhanced visual elements',
        'cta' => 'Call-to-action component for user engagement',
        'list-item' => 'Styled list items for organized content',
        'modal' => 'Modal dialog component for overlaid content',
        'table' => 'Data table component with sorting and filtering',
        'widget' => 'Multi-purpose widget component with various layouts'
    ];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $this->info('Starting Bonsai initialization...');

        // Step 1: Create required directories
        $this->createDirectories();

        // Step 2: Install all components
        $this->installComponents();

        // Verify components were installed
        if (!$this->verifyComponents()) {
            $this->error('Component installation failed. Please check the component templates.');
            return;
        }

        // Step 3: Create sections for components
        $this->createSections();

        // Step 4: Create layout
        $this->createLayout();

        // Step 5: Create the Components page
        $this->createComponentsPage();

        $this->info('🌳 Bonsai initialization completed successfully!');
    }

    protected function createDirectories()
    {
        $directories = [
            resource_path('views/bonsai'),
            resource_path('views/bonsai/components'),
            resource_path('views/bonsai/sections'),
            resource_path('views/bonsai/layouts'),
            resource_path('views/templates'),
        ];

        foreach ($directories as $directory) {
            if (!$this->files->isDirectory($directory)) {
                $this->files->makeDirectory($directory, 0755, true);
                $this->info("Created directory: {$directory}");
            }
        }
    }

    protected function installComponents()
    {
        $this->info('Installing components...');
        
        // First, verify component templates exist in bonsai-cli
        foreach ($this->components as $component => $description) {
            $templatePath = __DIR__ . "/../../templates/components/{$component}.blade.php";
            
            if (!file_exists($templatePath)) {
                $this->warn("Template not found for component: {$component}");
                continue;
            }

            $this->call('bonsai:component', [
                'name' => $component
            ]);

            // Verify component was installed
            $componentPath = resource_path("views/bonsai/components/{$component}.blade.php");
            if (!file_exists($componentPath)) {
                $this->error("Failed to install component: {$component}");
            }
        }
    }

    protected function verifyComponents()
    {
        $allInstalled = true;
        foreach ($this->components as $component => $description) {
            $componentPath = resource_path("views/bonsai/components/{$component}.blade.php");
            if (!file_exists($componentPath)) {
                $this->error("Component not found: {$component}");
                $allInstalled = false;
            }
        }
        return $allInstalled;
    }

    protected function createSections()
    {
        $this->info('Creating component sections...');
        
        // Only create sections for components that exist
        if ($this->componentExists('hero')) {
            $this->call('bonsai:section', [
                'name' => 'hero-example',
                '--component' => 'hero'
            ]);
        }

        if ($this->componentExists('faq')) {
            $this->call('bonsai:section', [
                'name' => 'faq-example',
                '--component' => 'faq'
            ]);
        }

        if ($this->componentExists('slideshow')) {
            $this->call('bonsai:section', [
                'name' => 'slideshow-example',
                '--component' => 'slideshow'
            ]);
        }
    }

    protected function componentExists($component)
    {
        return file_exists(resource_path("views/bonsai/components/{$component}.blade.php"));
    }

    protected function createLayout()
    {
        $this->info('Creating components layout...');
        
        // Only include sections for components that exist
        $sections = [];
        if ($this->componentExists('hero')) {
            $sections[] = 'hero-example';
        }
        if ($this->componentExists('slideshow')) {
            $sections[] = 'slideshow-example';
        }
        if ($this->componentExists('faq')) {
            $sections[] = 'faq-example';
        }

        if (!empty($sections)) {
            $this->call('bonsai:layout', [
                'name' => 'components',
                '--sections' => implode(',', $sections)
            ]);
        } else {
            $this->warn('No sections available for layout creation.');
        }
    }

    protected function createComponentsPage()
    {
        $pageTitle = 'Components';
        $pageSlug = 'components';
        
        $pageExists = DB::table('posts')
            ->where('post_type', 'page')
            ->where('post_name', $pageSlug)
            ->exists();

        if (!$pageExists) {
            $pageId = wp_insert_post([
                'post_title'   => $pageTitle,
                'post_name'    => $pageSlug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => [
                    '_wp_page_template' => 'template-components.blade.php',
                ],
            ]);

            if (is_wp_error($pageId)) {
                $this->error("Failed to create the Components page: " . $pageId->get_error_message());
                return;
            }

            $this->info("Created Components page with ID: {$pageId}");
        }

        // Create the template file
        $templatePath = resource_path("views/template-components.blade.php");
        if (!$this->files->exists($templatePath)) {
            $stubContent = $this->getTemplateStubContent($pageTitle);
            $this->files->put($templatePath, $stubContent);
            $this->info("Created Blade template: {$templatePath}");
        }
    }

    protected function getTemplateStubContent($title)
    {
        $componentSections = '';
        
        foreach ($this->components as $component => $description) {
            if ($this->componentExists($component)) {
                $componentTitle = str_replace('-', ' ', ucwords($component));
                $componentSections .= <<<BLADE
                    
                    {{-- {$componentTitle} Component --}}
                    <div class="mt-16">
                        <h2 class="text-2xl font-semibold mb-4">{$componentTitle}</h2>
                        <p class="text-gray-600 mb-6">{$description}</p>
                        <div class="bg-white rounded-lg p-6 shadow-lg">
                            @if(View::exists('bonsai.components.{$component}'))
                                <x-{$component} {{\$this->getExampleData('{$component}')}} />
                            @else
                                <div class="text-red-500">Component not found: {$component}</div>
                            @endif
                        </div>
                    </div>
                BLADE;
            }
        }

        return <<<BLADE
{{--
    Template Name: Components Template
--}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto py-10 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold mb-4">{$title} Library</h1>
            <p class="text-xl text-gray-600 mb-12">Explore our collection of reusable Blade components for building beautiful web interfaces.</p>

            {$componentSections}
        </div>
    </div>
@endsection

@php
function getExampleData(\$component) {
    switch (\$component) {
        case 'hero':
            return 'title="Welcome to Components" subtitle="Explore our library" description="Build beautiful interfaces with our component library" buttonText="Get Started"';
        case 'faq':
            return ':faqs="[
                [\'question\' => \'What are components?\', \'answer\' => \'Reusable building blocks for web interfaces\'],
                [\'question\' => \'How do I use them?\', \'answer\' => \'Import them into your templates and pass the required props\']
            ]"';
        // Add more examples for other components
        default:
            return '';
    }
}
@endphp
BLADE;
    }
}