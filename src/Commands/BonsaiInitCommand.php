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
        'accordion' => 'Interactive accordion component for toggling content visibility',
        'alert' => 'Contextual feedback messages for typical user actions',
        'button' => 'Customizable button component with multiple variants',
        'card-component' => 'Versatile card component for displaying content',
        'card-featured' => 'Featured card with enhanced visual elements',
        'cta' => 'Call-to-action component for user engagement',
        'faq' => 'Frequently asked questions component with expandable answers',
        'hero' => 'Hero section for prominent page headers',
        'list-item' => 'Styled list items for organized content',
        'modal' => 'Modal dialog component for overlaid content',
        'slideshow' => 'Dynamic slideshow for showcasing content',
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

        // Step 1: Install all components
        $this->installComponents();

        // Step 2: Create sections for components
        $this->createSections();

        // Step 3: Create layout
        $this->createLayout();

        // Step 4: Create the Components page
        $this->createComponentsPage();

        $this->info('🌳 Bonsai initialization completed successfully!');
    }

    protected function installComponents()
    {
        $this->info('Installing components...');
        foreach ($this->components as $component => $description) {
            $this->call('bonsai:component', [
                'name' => $component
            ]);
        }
    }

    protected function createSections()
    {
        $this->info('Creating component sections...');
        
        // Create example sections for each component type
        $this->call('bonsai:section', [
            'name' => 'hero-example',
            '--component' => 'hero'
        ]);

        $this->call('bonsai:section', [
            'name' => 'faq-example',
            '--component' => 'faq'
        ]);

        $this->call('bonsai:section', [
            'name' => 'slideshow-example',
            '--component' => 'slideshow'
        ]);

        // ... add more sections as needed
    }

    protected function createLayout()
    {
        $this->info('Creating components layout...');
        $this->call('bonsai:layout', [
            'name' => 'components',
            '--sections' => 'hero-example,slideshow-example,faq-example'
        ]);
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
            $componentTitle = str_replace('-', ' ', ucwords($component));
            $componentSections .= <<<BLADE
                
                {{-- {$componentTitle} Component --}}
                <div class="mt-16">
                    <h2 class="text-2xl font-semibold mb-4">{$componentTitle}</h2>
                    <p class="text-gray-600 mb-6">{$description}</p>
                    <div class="bg-white rounded-lg p-6 shadow-lg">
                        @php
                            \$exampleData = \$this->getExampleData('{$component}');
                        @endphp
                        <x-{$component} {{\$exampleData}} />
                    </div>
                </div>
            BLADE;
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