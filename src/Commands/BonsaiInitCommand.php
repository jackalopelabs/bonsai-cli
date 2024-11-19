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
    
        // Ask about configuration preference upfront
        $useDefault = !$this->confirm('Would you like to customize component configurations? (Default: No)', false);
    
        // Store the preference for use in other commands
        if ($useDefault) {
            $this->info('Using default configurations for all components...');
        }
    
        // Step 1: Create required directories
        $this->createDirectories();
    
        // Step 2: Install all components
        $this->installComponents($useDefault);
    
        // Step 3: Create sections for components
        $this->createSections($useDefault);
    
        // Step 4: Create layout
        $this->createLayout();
    
        // Step 5: Create the Components page
        $this->createComponentsPage();
    
        // Step 6: Setup local config directory
        $this->setupLocalConfig();
    
        $this->info('🌳 Bonsai initialization completed successfully!');
        $this->info("\nNext steps:");
        $this->line(" 1. Create your site configuration in config/bonsai/");
        $this->line(" 2. Run 'wp acorn bonsai:generate [template]' to generate your site");
        $this->line(" 3. Available templates: cypress, jackalope (or create your own)");
    }

    protected function createDirectories()
    {
        $directories = [
            resource_path('views/bonsai'),
            resource_path('views/bonsai/components'),
            resource_path('views/bonsai/sections'),
            resource_path('views/bonsai/layouts'),
            resource_path('views/templates'),
            base_path('config/bonsai')
        ];

        foreach ($directories as $directory) {
            if (!$this->files->isDirectory($directory)) {
                $this->files->makeDirectory($directory, 0755, true);
                $this->info("Created directory: {$directory}");
            }
        }
    }

    protected function setupLocalConfig()
    {
        $configDir = base_path('config/bonsai');
        $readmePath = "{$configDir}/README.md";

        if (!$this->files->exists($readmePath)) {
            $readmeContent = <<<MD
# Bonsai Configuration

This directory contains your site configurations for Bonsai CLI.

## Usage

1. Create a new .yml configuration file:
   ```bash
   my-site.yml
   ```

2. Generate your site:
   ```bash
   wp acorn bonsai:generate my-site
   ```

## Available Templates

You can also use pre-built templates:

- `cypress` - Modern SaaS landing page
- `jackalope` - Agency/portfolio site
- (more coming soon)

Example:
```bash
wp acorn bonsai:generate cypress
```

## Configuration Structure

```yaml
name: My Site
description: Site description
version: 1.0.0

# Components to install
components:
  - hero
  - faq
  - slideshow

# Section configurations
sections:
  homepage_hero:
    component: hero
    data:
      title: "Welcome"
      # ... component-specific data

# Layout definitions
layouts:
  home:
    sections:
      - homepage_hero
      - features_faq

# Page configurations
pages:
  home:
    title: "Home"
    layout: home
```

For more information, visit the Bonsai CLI documentation.
MD;
            
            $this->files->put($readmePath, $readmeContent);
            $this->info("Created config README: {$readmePath}");
        }

        // Create example.yml if it doesn't exist
        $examplePath = "{$configDir}/example.yml";
        if (!$this->files->exists($examplePath)) {
            $exampleContent = $this->getExampleConfig();
            $this->files->put($examplePath, $exampleContent);
            $this->info("Created example config: {$examplePath}");
        }
    }

    protected function getExampleConfig()
    {
        return <<<YAML
name: My Bonsai Site
description: Custom site configuration
version: 1.0.0

components:
  - hero
  - faq
  - slideshow

sections:
  home_hero:
    component: hero
    data:
      title: "Welcome to My Site"
      subtitle: "Built with Bonsai"
      description: "A modern WordPress site"
      imagePath: "images/hero.jpg"
      l1: "Feature One"
      l2: "Feature Two"
      l3: "Feature Three"
      l4: "Feature Four"
      primaryText: "Get Started"
      primaryLink: "#contact"
      secondaryText: "Learn More"

layouts:
  main:
    sections:
      - home_hero

pages:
  home:
    title: "Home"
    layout: main
YAML;
    }

    protected function installComponents($useDefault = false)
    {
        $this->info('Installing components...');
        foreach ($this->components as $component => $description) {
            $this->call('bonsai:component', [
                'name' => $component,
                '--default' => $useDefault,  // Pass this flag to the component command
            ]);
        }
    }

    protected function createSections($useDefault = false)
    {
        $this->info('Creating example sections...');
        
        $this->call('bonsai:section', [
            'name' => 'hero-example',
            '--component' => 'hero',
            '--default' => $useDefault,
        ]);
    
        $this->call('bonsai:section', [
            'name' => 'faq-example',
            '--component' => 'faq',
            '--default' => $useDefault,
        ]);
    
        $this->call('bonsai:section', [
            'name' => 'slideshow-example',
            '--component' => 'slideshow',
            '--default' => $useDefault,
        ]);
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
                        @if(View::exists('bonsai.components.{$component}'))
                            <x-{$component} {{\$this->getExampleData('{$component}')}} />
                        @else
                            <div class="text-red-500">Component not found: {$component}</div>
                        @endif
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