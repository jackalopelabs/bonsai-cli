<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Jackalopelabs\BonsaiCli\Traits\HandlesTemplatePaths;

class BonsaiInitCommand extends Command
{
    use HandlesTemplatePaths;

    protected $signature = 'bonsai:init';
    protected $description = 'Initialize project by creating a Components page and setting up default templates';

    protected $files;

    protected $directories = [
        'resources/views/bonsai',
        'resources/views/bonsai/components',
        'resources/views/bonsai/sections',
        'resources/views/bonsai/layouts',
        'resources/views/templates',
        'config/bonsai',
        'scripts',
    ];

    protected $components = [
        'hero' => 'Hero section for prominent page headers'
    ];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $this->info('Starting Bonsai initialization...');
    
        try {
            // Create directories
            $this->createDirectories();
    
            // Install bonsai.sh script
            $this->installBonsaiScript();
    
            // Ask about configuration preference upfront
            $useDefault = !$this->confirm('Would you like to customize component configurations? (Default: No)', false);
    
            // Step 1: Setup component namespace and base class
            $this->setupComponentNamespace();
    
            // Step 2: Install all components
            $this->installComponents($useDefault);
    
            // Step 3: Create sections for components
            $this->createSections($useDefault);
    
            // Step 4: Create layout
            $this->createLayout();
    
            // Step 5: Create the Components page
            $this->createComponentsPage();
    
            // Step 6: Setup local config directory with templates subdirectory
            $this->createConfigDirectory();
    
            $this->info('🌳 Bonsai initialization completed successfully!');
            $this->info("\nNext steps:");
            $this->line(" 1. Create your site configuration in config/bonsai/templates/");
            $this->line(" 2. Run 'wp acorn bonsai:generate [template]' to generate your site");
            $this->line(" 3. Available templates: cypress, jackalope (or create your own)");
    
        } catch (\Exception $e) {
            $this->error("Initialization failed: " . $e->getMessage());
            return 1;
        }
    
        return 0;
    }

    protected function createDirectories()
    {
        foreach ($this->directories as $directory) {
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
            try {
                $componentName = is_array($component) ? $component : $component;
                $this->info("Installing component: {$componentName}");

                // 1. Create the component class
                $this->createComponentClass($componentName);

                // 2. Copy the component template
                $this->copyComponentTemplate($componentName);

            } catch (\Exception $e) {
                $this->warn("Warning: Could not generate component '{$componentName}': " . $e->getMessage());
            }
        }
    }

    protected function createComponentClass($componentName)
    {
        $className = str_replace(['-', '_'], '', ucwords($componentName, '-_'));
        $classPath = app_path("View/Components/Bonsai/{$className}.php");
        
        // Debug info
        $this->info("Creating component class: {$className}");
        $this->info("Class path: {$classPath}");
        
        if (!$this->files->exists($classPath)) {
            $content = <<<PHP
<?php

namespace App\View\Components\Bonsai;

use Illuminate\View\Component;

class {$className} extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string \$title = null,
        public ?string \$subtitle = null,
        public ?string \$description = null,
        public ?string \$imagePath = null,
        public ?string \$l1 = null,
        public ?string \$l2 = null,
        public ?string \$l3 = null,
        public ?string \$l4 = null,
        public ?string \$primaryText = null,
        public ?string \$primaryLink = null,
        public ?string \$secondaryText = null,
        public ?string \$secondaryLink = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('bonsai.components.{$componentName}');
    }
}
PHP;
            
            try {
                // Ensure directory exists
                $dir = dirname($classPath);
                if (!$this->files->isDirectory($dir)) {
                    $this->files->makeDirectory($dir, 0755, true);
                }

                $this->files->put($classPath, $content);
                $this->info("✓ Created component class: {$classPath}");
                
                // Debug - verify file contents
                $this->info("File contents:");
                $this->line($this->files->get($classPath));
            } catch (\Exception $e) {
                $this->error("Failed to create component class: " . $e->getMessage());
                throw $e;
            }
        }
    }

    protected function getComponentProperties($componentName)
    {
        // Define properties for each component type
        $properties = [
            'hero' => [
                'title' => 'string',
                'subtitle' => 'string',
                'description' => 'string',
                'imagePath' => 'string',
                'l1' => 'string',
                'l2' => 'string',
                'l3' => 'string',
                'l4' => 'string',
                'primaryText' => 'string',
                'primaryLink' => 'string',
                'secondaryText' => 'string',
                'secondaryLink' => 'string',
            ],
            'faq' => [
                'title' => 'string',
                'faqs' => 'array',
            ],
            // Add more component properties as needed
        ];

        return $properties[$componentName] ?? [];
    }

    protected function buildConstructorParams($props)
    {
        $params = [];
        foreach ($props as $prop => $type) {
            $params[] = "public ?{$type} \${$prop} = null";
        }
        return implode(",\n        ", $params);
    }

    protected function copyComponentTemplate($componentName)
    {
        // Update possible paths to include the package templates directory
        $possiblePaths = [
            base_path("templates/components/{$componentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$componentName}.blade.php",
            base_path("resources/views/bonsai/components/{$componentName}.blade.php")
        ];

        $templatePath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $templatePath = $path;
                $this->info("Found template at: {$path}");
                break;
            }
        }

        if (!$templatePath) {
            $this->warn("No template found for component: {$componentName}");
            $this->createBasicComponent($componentName);
            return;
        }

        // Ensure the bonsai components directory exists
        $targetDir = resource_path("views/bonsai/components");
        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
        }

        // Copy component to bonsai components directory
        $targetPath = "{$targetDir}/{$componentName}.blade.php";
        $this->files->copy($templatePath, $targetPath);
        $this->info("Component template installed at: {$targetPath}");
    }

    protected function createSections($useDefault = false)
    {
        $this->info('Creating example sections...');
        
        // Create header section
        $this->call('bonsai:section', [
            'name' => 'header',
            '--component' => 'header',
            '--default' => $useDefault,
        ]);
        
        // Create hero section
        $this->call('bonsai:section', [
            'name' => 'home_hero',
            '--component' => 'hero',
            '--default' => $useDefault,
        ]);
    }

    protected function createLayout()
    {
        $this->info('Creating components layout...');
        $this->call('bonsai:layout', [
            'name' => 'cypress',
            '--sections' => 'header,home_hero'
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
                    '_wp_page_template' => $this->getWordPressTemplatePath('components'),
                    '_bonsai_generated' => 'true',
                    '_bonsai_template' => 'components',
                ],
            ]);

            if (is_wp_error($pageId)) {
                $this->error("Failed to create the Components page: " . $pageId->get_error_message());
                return;
            }

            $this->info("Created Components page with ID: {$pageId}");
        }

        // Create the template file
        $templatePath = $this->getTemplateFilePath('components');
        if (!$this->files->exists($templatePath)) {
            // Ensure directory exists
            if (!$this->files->exists(dirname($templatePath))) {
                $this->files->makeDirectory(dirname($templatePath), 0755, true);
            }
            
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
                            <x-bonsai::{$component} {{\$this->getExampleData('{$component}')}} />
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

    protected function setupComponentNamespace()
    {
        // Create the App\View\Components\Bonsai directory if it doesn't exist
        $componentsDir = app_path('View/Components/Bonsai');
        if (!$this->files->isDirectory($componentsDir)) {
            $this->files->makeDirectory($componentsDir, 0755, true);
        }

        // Create a base component class
        $baseComponentPath = "{$componentsDir}/BaseComponent.php";
        if (!$this->files->exists($baseComponentPath)) {
            $content = <<<PHP
<?php

namespace App\View\Components\Bonsai;

use Illuminate\View\Component;

class BaseComponent extends Component
{
    public function render()
    {
        // Get the component name from the class name
        \$name = strtolower(class_basename(\$this));
        return view("bonsai.components.{\$name}");
    }
}
PHP;
            $this->files->put($baseComponentPath, $content);
        }
    }

    protected function installBonsaiScript()
    {
        $this->info('Installing bonsai.sh script...');

        // Source path in the package
        $sourcePath = __DIR__ . '/../../scripts/bonsai.sh';
        
        // Target path in the project
        $targetPath = base_path('scripts/bonsai.sh');

        try {
            // Copy the script
            if (!$this->files->exists($sourcePath)) {
                throw new \Exception("Source script not found: {$sourcePath}");
            }

            $this->files->copy($sourcePath, $targetPath);

            // Make it executable
            chmod($targetPath, 0755);

            $this->info("✓ Installed bonsai.sh script");
            $this->info("  Location: scripts/bonsai.sh");
            $this->info("  Permissions: 755 (executable)");

        } catch (\Exception $e) {
            $this->error("Failed to install bonsai.sh script: " . $e->getMessage());
            throw $e;
        }
    }

    protected function createConfigDirectory()
    {
        $configPath = $this->laravel->basePath('config/bonsai');
        $templatesPath = $configPath . '/templates';  // Add templates subdirectory

        if (!$this->files->exists($configPath)) {
            $this->files->makeDirectory($configPath, 0755, true);
        }

        if (!$this->files->exists($templatesPath)) {
            $this->files->makeDirectory($templatesPath, 0755, true);
        }

        // Copy example config to templates directory
        $exampleConfig = __DIR__ . '/../../config/templates/bonsai.yml';
        $targetConfig = $templatesPath . '/example.yml';

        if (!$this->files->exists($targetConfig)) {
            if (!$this->files->exists($exampleConfig)) {
                $this->error("Source config not found at: {$exampleConfig}");
                return;
            }
            $this->files->copy($exampleConfig, $targetConfig);
            $this->info("Created example config at: {$targetConfig}");
        }
    }
}