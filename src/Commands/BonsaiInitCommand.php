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
        $hasErrors = false;
    
        // Create directories
        try {
            $this->createDirectories();
        } catch (\Exception $e) {
            $this->warn("Error creating directories: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Install bonsai.sh script
        try {
            $this->installBonsaiScript();
        } catch (\Exception $e) {
            $this->warn("Error installing bonsai.sh script: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Configure Tailwind
        try {
            $this->configureTailwind();
        } catch (\Exception $e) {
            $this->warn("Error configuring Tailwind: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Configure CSS
        try {
            $this->configureCSS();
        } catch (\Exception $e) {
            $this->warn("Error configuring CSS: " . $e->getMessage());
            $hasErrors = true;
        }

        // Configure Vite
        try {
            $this->configureVite();
        } catch (\Exception $e) {
            $this->warn("Error configuring Vite: " . $e->getMessage());
            $hasErrors = true;
        }

        // Configure Scripts
        try {
            $this->configureScripts();
        } catch (\Exception $e) {
            $this->warn("Error configuring scripts: " . $e->getMessage());
            $hasErrors = true;
        }

        // Configure Composer
        try {
            $this->configureComposer();
        } catch (\Exception $e) {
            $this->warn("Error configuring composer: " . $e->getMessage());
            $hasErrors = true;
        }

        // Ask about configuration preference upfront
        $useDefault = !$this->confirm('Would you like to customize component configurations? (Default: No)', false);
    
        // Step 1: Setup component namespace and base class
        try {
            $this->setupComponentNamespace();
        } catch (\Exception $e) {
            $this->warn("Error setting up component namespace: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Step 2: Install all components
        try {
            $this->installComponents($useDefault);
        } catch (\Exception $e) {
            $this->warn("Error installing components: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Step 3: Create sections for components
        try {
            $this->createSections($useDefault);
        } catch (\Exception $e) {
            $this->warn("Error creating sections: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Step 4: Create layout
        try {
            $this->createLayout();
        } catch (\Exception $e) {
            $this->warn("Error creating layout: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Step 5: Create the Components page
        try {
            $this->createComponentsPage();
        } catch (\Exception $e) {
            $this->warn("Error creating components page: " . $e->getMessage());
            $hasErrors = true;
        }
    
        // Step 6: Setup local config directory with templates subdirectory
        try {
            $this->createConfigDirectory();
        } catch (\Exception $e) {
            $this->warn("Error creating config directory: " . $e->getMessage());
            $hasErrors = true;
        }
    
        if ($hasErrors) {
            $this->info('🌳 Bonsai initialization completed with some warnings.');
            $this->info('Some steps may have been skipped. Check the warnings above for details.');
        } else {
            $this->info('🌳 Bonsai initialization completed successfully!');
        }
        
        $this->info("\nNext steps:");
        $this->line(" 1. Create your site configuration in config/bonsai/templates/");
        $this->line(" 2. Run 'wp acorn bonsai:generate [template]' to generate your site");
        $this->line(" 3. Available templates: cypress, jackalope (or create your own)");
    
        return $hasErrors ? 1 : 0;
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
            // Check if source script exists
            if (!$this->files->exists($sourcePath)) {
                $this->warn("Source script not found at: {$sourcePath}");
                $this->info("Skipping bonsai.sh script installation");
                return;
            }

            // Ensure scripts directory exists
            $scriptsDir = dirname($targetPath);
            if (!$this->files->isDirectory($scriptsDir)) {
                try {
                    $this->files->makeDirectory($scriptsDir, 0755, true);
                    $this->info("Created directory: {$scriptsDir}");
                } catch (\Exception $e) {
                    $this->warn("Could not create scripts directory: {$e->getMessage()}");
                    $this->info("Skipping bonsai.sh script installation");
                    return;
                }
            }

            // Copy the script
            try {
                $this->files->copy($sourcePath, $targetPath);
                
                // Make it executable
                chmod($targetPath, 0755);
                
                $this->info("✓ Installed bonsai.sh script");
                $this->info("  Location: scripts/bonsai.sh");
                $this->info("  Permissions: 755 (executable)");
            } catch (\Exception $e) {
                $this->warn("Could not copy bonsai.sh script: {$e->getMessage()}");
                $this->info("Skipping bonsai.sh script installation");
            }
        } catch (\Exception $e) {
            $this->warn("Failed to install bonsai.sh script: {$e->getMessage()}");
            $this->info("Continuing with initialization...");
            // Don't throw the exception, just log it and continue
        }
    }

    protected function createConfigDirectory()
    {
        $configPath = $this->laravel->basePath('config/bonsai');
        $templatesPath = $configPath . '/templates';

        if (!$this->files->exists($configPath)) {
            $this->files->makeDirectory($configPath, 0755, true);
        }

        if (!$this->files->exists($templatesPath)) {
            $this->files->makeDirectory($templatesPath, 0755, true);
        }

        // Copy example config from package templates
        $exampleConfig = __DIR__ . '/../../config/templates/example.yml';
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

    protected function configureTailwind()
    {
        $this->info('Configuring Tailwind...');

        // Update tailwind.config.ts
        $tailwindConfigPath = base_path('tailwind.config.ts');
        if ($this->files->exists($tailwindConfigPath)) {
            $tailwindConfig = $this->files->get($tailwindConfigPath);
            $modified = false;

            // Add midnight color if it doesn't exist
            if (!str_contains($tailwindConfig, 'midnight:')) {
                // Find the colors object
                $pattern = '/(colors:\s*{[^}]*})/s';
                if (preg_match($pattern, $tailwindConfig, $matches)) {
                    // Check if the colors object ends with a comma
                    $colorsObj = $matches[1];
                    $replacement = rtrim($colorsObj, '}');
                    $replacement = rtrim($replacement, ',') . ",\n      midnight: {\n        950: '#060614'\n      }\n    }";
                    $tailwindConfig = str_replace($colorsObj, $replacement, $tailwindConfig);
                    $modified = true;
                }
            }
            
            // Add darkMode configuration if it doesn't exist
            if (!str_contains($tailwindConfig, "darkMode:")) {
                // Add darkMode at the root level, after content array
                $pattern = '/(content:\s*\[[^\]]*\],)/s';
                $replacement = "$1\n  darkMode: 'class',";
                $tailwindConfig = preg_replace($pattern, $replacement, $tailwindConfig);
                $modified = true;
            }

            if ($modified) {
                // Clean up any double commas
                $tailwindConfig = preg_replace('/,(\s*,)+/', ',', $tailwindConfig);
                // Clean up any trailing commas before closing braces
                $tailwindConfig = preg_replace('/,(\s*})/', '$1', $tailwindConfig);
                
                $this->files->put($tailwindConfigPath, $tailwindConfig);
                $this->info('Updated tailwind.config.ts');
            } else {
                $this->info('Tailwind configuration already up to date');
            }
        } else {
            $this->warn('tailwind.config.ts not found');
        }
    }

    protected function configureCSS()
    {
        $this->info('Configuring CSS for Tailwind 4...');

        // Update app.css to add Tailwind 4 specific CSS
        $appCssPath = base_path('resources/css/app.css');

        if ($this->files->exists($appCssPath)) {
            $appCss = $this->files->get($appCssPath);
            
            // Check if the Tailwind 4 CSS is already added
            if (!str_contains($appCss, "@theme {")) {
                $tailwind4Css = <<<CSS

@theme {
    --color-midnight-950: #060614;
}

@layer base {
    :root {
        --background: 255 255 255;
        --foreground: 15 23 42;
    }

    .dark {
        --background: 6 6 20;
        --foreground: 255 255 255;
    }

    body {
        @apply text-gray-900 dark:text-white bg-white dark:bg-midnight-950;
    }

    a {
        @apply no-underline;
        text-decoration: none !important;
    }
    
    a:hover {
        text-decoration: none !important;
    }
}
CSS;

                // Find the position after the imports but before any other content
                $importLines = [
                    "@import \"tailwindcss\" theme(static);",
                    "@source \"../views/\";",
                    "@source \"../../app/\";"
                ];
                
                $lastImportPos = 0;
                foreach ($importLines as $importLine) {
                    if (str_contains($appCss, $importLine)) {
                        $pos = strpos($appCss, $importLine) + strlen($importLine);
                        $lastImportPos = max($lastImportPos, $pos);
                    }
                }
                
                if ($lastImportPos > 0) {
                    // Insert after the last import
                    $appCss = substr_replace($appCss, $tailwind4Css, $lastImportPos, 0);
                } else {
                    // If imports not found, append to the end
                    $appCss .= $tailwind4Css;
                }
                
                $this->files->put($appCssPath, $appCss);
                $this->info('Updated app.css with Tailwind 4 configuration');
            } else {
                $this->info('app.css already contains Tailwind 4 configuration');
            }
        } else {
            $this->warn('app.css not found in resources/css directory');
            $this->info('Creating resources/css/app.css with Tailwind 4 configuration');
            
            // Create the directory if it doesn't exist
            if (!$this->files->isDirectory(base_path('resources/css'))) {
                $this->files->makeDirectory(base_path('resources/css'), 0755, true);
            }
            
            // Create a basic app.css file with the Tailwind 4 configuration
            $basicAppCss = <<<CSS
@import "tailwindcss" theme(static);
@source "../views/";
@source "../../app/";

@theme {
    --color-midnight-950: #060614;
}

@layer base {
    :root {
        --background: 255 255 255;
        --foreground: 15 23 42;
    }

    .dark {
        --background: 6 6 20;
        --foreground: 255 255 255;
    }

    body {
        @apply text-gray-900 dark:text-white bg-white dark:bg-midnight-950;
    }
}
CSS;
            
            $this->files->put($appCssPath, $basicAppCss);
        }
    }

    protected function configureVite()
    {
        $this->info('Configuring Vite for Tailwind...');

        // Determine the project structure and find the vite.config.js file
        $projectRoot = getcwd(); // Current working directory
        
        // Possible locations for vite.config.js
        $possibleLocations = [
            $projectRoot . '/vite.config.js',                           // Radicle (project root)
            $projectRoot . '/vite.config.ts',                           // Radicle with TypeScript
            $projectRoot . '/web/app/themes/sage/vite.config.js',       // Sage 11 in Bedrock
            $projectRoot . '/web/app/themes/sage/vite.config.ts',       // Sage 11 in Bedrock with TypeScript
        ];
        
        // Try to find theme name if in a Bedrock structure
        $themesDir = $projectRoot . '/web/app/themes';
        if (is_dir($themesDir)) {
            $themes = array_filter(scandir($themesDir), function($item) use ($themesDir) {
                return $item !== '.' && $item !== '..' && is_dir($themesDir . '/' . $item);
            });
            
            foreach ($themes as $theme) {
                $possibleLocations[] = $projectRoot . '/web/app/themes/' . $theme . '/vite.config.js';
                $possibleLocations[] = $projectRoot . '/web/app/themes/' . $theme . '/vite.config.ts';
            }
        }
        
        // Find the first existing vite config file
        $viteConfigPath = null;
        foreach ($possibleLocations as $location) {
            if (file_exists($location)) {
                $viteConfigPath = $location;
                break;
            }
        }
        
        if ($viteConfigPath) {
            $this->info("Found Vite config at: {$viteConfigPath}");
            $viteConfig = file_get_contents($viteConfigPath);
            
            // Check if the config already has the custom Tailwind configuration
            if (str_contains($viteConfig, 'darkMode: \'class\'')) {
                $this->info('Vite configuration already contains custom Tailwind settings');
                return;
            }
            
            // Try different patterns for tailwindcss() call
            $patterns = [
                '/tailwindcss\(\),/',                // tailwindcss(),
                '/tailwindcss\(\s*\),/',             // tailwindcss( ),
                '/tailwindcss\s*\(\s*\),/',          // tailwindcss ( ),
                '/tailwindcss\s*\(\s*\)\s*,/',       // tailwindcss ( ) ,
                '/tailwindcss\(\)/',                 // tailwindcss() without comma
                '/tailwindcss\s*\(\s*\)/'            // tailwindcss ( ) without comma
            ];
            
            $replacement = <<<'JS'
tailwindcss({
      config: {
        darkMode: 'class',
        content: [
          "./resources/**/*.blade.php",
          "./resources/**/*.js",
          "./resources/**/*.vue",
        ],
        theme: {
          extend: {
            colors: {
              'midnight-950': 'var(--color-midnight-950)',
            },
          },
        },
      },
    }),
JS;
            
            $modified = false;
            
            // Try each pattern
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $viteConfig)) {
                    $viteConfig = preg_replace($pattern, $replacement, $viteConfig);
                    file_put_contents($viteConfigPath, $viteConfig);
                    $this->info('Updated Vite configuration with custom Tailwind settings');
                    $modified = true;
                    break;
                }
            }
            
            if (!$modified) {
                $this->warn('Could not find tailwindcss() in the Vite configuration');
                $this->info('Please manually update your Vite configuration with the custom Tailwind settings');
                $this->line('Add the following configuration to your Vite config file:');
                $this->line($replacement);
            }
        } else {
            $this->warn('No vite.config.js or vite.config.ts found in standard Roots project locations');
            $this->info('Please manually update your Vite configuration with the custom Tailwind settings');
            
            // Offer to create a basic vite.config.js file
            if ($this->confirm('Would you like to create a basic vite.config.js file in the current directory?', true)) {
                $this->createBasicViteConfig($projectRoot . '/vite.config.js');
            } else {
                $this->line('You can manually add the following configuration to your Vite config file:');
                $this->line(<<<'JS'
tailwindcss({
      config: {
        darkMode: 'class',
        content: [
          "./resources/**/*.blade.php",
          "./resources/**/*.js",
          "./resources/**/*.vue",
        ],
        theme: {
          extend: {
            colors: {
              'midnight-950': 'var(--color-midnight-950)',
            },
          },
        },
      },
    }),
JS
                );
            }
        }
    }
    
    protected function createBasicViteConfig($path)
    {
        $basicViteConfig = <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    tailwindcss({
      config: {
        darkMode: 'class',
        content: [
          "./resources/**/*.blade.php",
          "./resources/**/*.js",
          "./resources/**/*.vue",
        ],
        theme: {
          extend: {
            colors: {
              'midnight-950': 'var(--color-midnight-950)',
            },
          },
        },
      },
    }),
  ],
});
JS;

        file_put_contents($path, $basicViteConfig);
        $this->info("Created basic vite.config.js at: {$path}");
    }

    protected function configureScripts()
    {
        $this->info('Configuring JavaScript...');

        // Create js directory if it doesn't exist
        $jsDir = base_path('resources/js');
        if (!$this->files->isDirectory($jsDir)) {
            $this->files->makeDirectory($jsDir, 0755, true);
            $this->info('Created js directory');
        }

        // Copy pixel-matrix.js
        $pixelMatrixTemplate = __DIR__ . '/../../templates/scripts/pixel-matrix.js';
        $pixelMatrixTarget = $jsDir . '/pixel-matrix.js';
        
        if ($this->files->exists($pixelMatrixTemplate)) {
            $this->files->copy($pixelMatrixTemplate, $pixelMatrixTarget);
            $this->info('Created pixel-matrix.js');
        } else {
            // Try to convert the TS file to JS if it exists
            $pixelMatrixTsTemplate = __DIR__ . '/../../templates/scripts/pixel-matrix.ts';
            if ($this->files->exists($pixelMatrixTsTemplate)) {
                // Simple conversion from TS to JS (removing type annotations)
                $tsContent = $this->files->get($pixelMatrixTsTemplate);
                $jsContent = preg_replace('/:\s*HTMLElement|\bas\s+HTMLElement|\<HTMLElement\>/', '', $tsContent);
                $this->files->put($pixelMatrixTarget, $jsContent);
                $this->info('Created pixel-matrix.js (converted from TS)');
            } else {
                $this->warn('Pixel matrix template not found, skipping');
                return;
            }
        }

        // Update existing app.js
        $appJsPath = $jsDir . '/app.js';
        if (!$this->files->exists($appJsPath)) {
            // Check if there's an app.ts file we can convert
            $appTsPath = base_path('resources/scripts/app.ts');
            if ($this->files->exists($appTsPath)) {
                // Simple conversion from TS to JS (removing type annotations)
                $tsContent = $this->files->get($appTsPath);
                $jsContent = preg_replace('/:\s*\w+|\bas\s+\w+|\<\w+\>/', '', $tsContent);
                $this->files->put($appJsPath, $jsContent);
                $this->info('Created app.js (converted from app.ts)');
            } else {
                // Create a basic app.js file
                $basicAppJs = <<<JS
import.meta.glob(["../images/**", "../fonts/**"]);
import alpine from "alpinejs";
import PixelMatrix from './pixel-matrix';

// Initialize PixelMatrix on pricing boxes
document.addEventListener('DOMContentLoaded', () => {
    const pricingBoxes = document.querySelectorAll('.pricing-box')
    pricingBoxes.forEach(box => new PixelMatrix(box))
});

document.addEventListener('alpine:init', () => {
    // Add dark mode store with simple state management
    alpine.store('darkMode', {
        on: true, // Default to dark mode
        
        init() {
            // Apply initial dark mode state to body
            document.body.classList.toggle('dark', this.on);
        },
        
        toggle() {
            this.on = !this.on;
            document.body.classList.toggle('dark', this.on);
        }
    });

    // Make darkMode accessible in Alpine components
    alpine.data('globalData', () => ({
        get darkMode() {
            return this.\$store.darkMode.on;
        }
    }));
});

// Start Alpine
alpine.start();
JS;
                $this->files->put($appJsPath, $basicAppJs);
                $this->info('Created basic app.js file');
            }
        } else {
            $appJs = $this->files->get($appJsPath);
            $modified = false;

            // Detect if this is a Sage 11 style app.js
            $isSage11Format = preg_match('/import\.meta\.glob\(\s*\[\s*[\'"]\.\.\/images\/\*\*[\'"]/', $appJs);
            
            if ($isSage11Format) {
                $this->info('Detected Sage 11 app.js format');
                
                // For Sage 11, we'll add our code in a way that preserves the existing structure
                // First, check if Alpine.js is already imported
                $hasAlpine = strpos($appJs, 'alpinejs') !== false || strpos($appJs, 'alpine') !== false;
                $hasPixelMatrix = strpos($appJs, 'PixelMatrix') !== false;
                
                if (!$hasAlpine || !$hasPixelMatrix) {
                    // Find the end of the import.meta.glob section
                    $globEndPos = strpos($appJs, ']);');
                    if ($globEndPos !== false) {
                        $insertPos = $globEndPos + 3; // After the ']);'
                        
                        $additionalCode = "\n\n";
                        
                        if (!$hasAlpine) {
                            $additionalCode .= "import alpine from 'alpinejs';\n";
                        }
                        
                        if (!$hasPixelMatrix) {
                            $additionalCode .= "import PixelMatrix from './pixel-matrix';\n";
                        }
                        
                        $additionalCode .= "\n// Initialize PixelMatrix on pricing boxes\n";
                        $additionalCode .= "document.addEventListener('DOMContentLoaded', () => {\n";
                        $additionalCode .= "  const pricingBoxes = document.querySelectorAll('.pricing-box');\n";
                        $additionalCode .= "  pricingBoxes.forEach(box => new PixelMatrix(box));\n";
                        $additionalCode .= "});\n\n";
                        
                        if (!$hasAlpine) {
                            $additionalCode .= "document.addEventListener('alpine:init', () => {\n";
                            $additionalCode .= "  // Add dark mode store with simple state management\n";
                            $additionalCode .= "  alpine.store('darkMode', {\n";
                            $additionalCode .= "    on: true, // Default to dark mode\n";
                            $additionalCode .= "    \n";
                            $additionalCode .= "    init() {\n";
                            $additionalCode .= "      // Apply initial dark mode state to body\n";
                            $additionalCode .= "      document.body.classList.toggle('dark', this.on);\n";
                            $additionalCode .= "    },\n";
                            $additionalCode .= "    \n";
                            $additionalCode .= "    toggle() {\n";
                            $additionalCode .= "      this.on = !this.on;\n";
                            $additionalCode .= "      document.body.classList.toggle('dark', this.on);\n";
                            $additionalCode .= "    }\n";
                            $additionalCode .= "  });\n\n";
                            $additionalCode .= "  // Make darkMode accessible in Alpine components\n";
                            $additionalCode .= "  alpine.data('globalData', () => ({\n";
                            $additionalCode .= "    get darkMode() {\n";
                            $additionalCode .= "      return this.\$store.darkMode.on;\n";
                            $additionalCode .= "    }\n";
                            $additionalCode .= "  }));\n";
                            $additionalCode .= "});\n\n";
                            $additionalCode .= "// Start Alpine\n";
                            $additionalCode .= "alpine.start();\n";
                        }
                        
                        $appJs = substr_replace($appJs, $additionalCode, $insertPos, 0);
                        $this->files->put($appJsPath, $appJs);
                        $this->info('Updated app.js with Bonsai functionality while preserving Sage 11 structure');
                        $modified = true;
                    }
                }
            } else {
                // Original code for non-Sage 11 app.js
                // Add PixelMatrix import if it doesn't exist
                if (!str_contains($appJs, "import PixelMatrix")) {
                    // Find the position after the last import statement
                    $lastImportPos = strrpos($appJs, "import");
                    if ($lastImportPos !== false) {
                        $endOfLine = strpos($appJs, "\n", $lastImportPos);
                        if ($endOfLine !== false) {
                            $appJs = substr_replace($appJs, "\nimport PixelMatrix from './pixel-matrix'", $endOfLine, 0);
                            $modified = true;
                        }
                    } else {
                        // No imports found, add at the beginning
                        $appJs = "import PixelMatrix from './pixel-matrix'\n" . $appJs;
                        $modified = true;
                    }
                }

                // Add PixelMatrix initialization if it doesn't exist
                if (!str_contains($appJs, "new PixelMatrix")) {
                    $initCode = "\n// Initialize PixelMatrix on pricing boxes\n";
                    $initCode .= "document.addEventListener('DOMContentLoaded', () => {\n";
                    $initCode .= "    const pricingBoxes = document.querySelectorAll('.pricing-box')\n";
                    $initCode .= "    pricingBoxes.forEach(box => new PixelMatrix(box))\n";
                    $initCode .= "})\n\n";

                    // Find the right position to insert - before Alpine initialization if it exists
                    $alpineInitPos = strpos($appJs, "Object.assign(window, { Alpine:");
                    
                    if ($alpineInitPos !== false) {
                        // Insert before Alpine initialization
                        $insertPos = $alpineInitPos;
                    } else {
                        // Insert at the end of the file
                        $insertPos = strlen($appJs);
                    }

                    $appJs = substr_replace($appJs, $initCode, $insertPos, 0);
                    $modified = true;
                }

                // Add dark mode functionality if it doesn't exist
                if (!str_contains($appJs, "alpine.store('darkMode'")) {
                    $darkModeCode = "\ndocument.addEventListener('alpine:init', () => {\n";
                    $darkModeCode .= "    // Add dark mode store with simple state management\n";
                    $darkModeCode .= "    alpine.store('darkMode', {\n";
                    $darkModeCode .= "        on: true, // Default to dark mode\n";
                    $darkModeCode .= "        \n";
                    $darkModeCode .= "        init() {\n";
                    $darkModeCode .= "            // Apply initial dark mode state to body\n";
                    $darkModeCode .= "            document.body.classList.toggle('dark', this.on);\n";
                    $darkModeCode .= "        },\n";
                    $darkModeCode .= "        \n";
                    $darkModeCode .= "        toggle() {\n";
                    $darkModeCode .= "            this.on = !this.on;\n";
                    $darkModeCode .= "            document.body.classList.toggle('dark', this.on);\n";
                    $darkModeCode .= "        }\n";
                    $darkModeCode .= "    });\n";
                    $darkModeCode .= "\n";
                    $darkModeCode .= "    // Make darkMode accessible in Alpine components\n";
                    $darkModeCode .= "    alpine.data('globalData', () => ({\n";
                    $darkModeCode .= "        get darkMode() {\n";
                    $darkModeCode .= "            return this.\$store.darkMode.on;\n";
                    $darkModeCode .= "        }\n";
                    $darkModeCode .= "    }));\n";
                    $darkModeCode .= "});\n\n";

                    // Find the right position to insert
                    // If there's an Alpine start line, replace it with our code + alpine.start()
                    $alpineStartPos = strpos($appJs, "Object.assign(window, { Alpine: alpine }).Alpine.start()");
                    $alpineStartPos2 = strpos($appJs, "alpine.start()");
                    
                    if ($alpineStartPos !== false) {
                        // Replace the Alpine start line
                        $appJs = str_replace(
                            "Object.assign(window, { Alpine: alpine }).Alpine.start()",
                            $darkModeCode . "// Start Alpine\nalpine.start()",
                            $appJs
                        );
                        $modified = true;
                    } elseif ($alpineStartPos2 !== false) {
                        // Insert before alpine.start()
                        $appJs = str_replace(
                            "alpine.start()",
                            $darkModeCode . "// Start Alpine\nalpine.start()",
                            $appJs
                        );
                        $modified = true;
                    } else {
                        // Add at the end of the file
                        $appJs .= $darkModeCode . "// Start Alpine\nalpine.start();\n";
                        $modified = true;
                    }
                }

                if ($modified) {
                    $this->files->put($appJsPath, $appJs);
                    $this->info('Updated app.js with PixelMatrix integration and dark mode functionality');
                } else {
                    $this->info('app.js already contains PixelMatrix integration and dark mode functionality');
                }
            }
        }
    }

    protected function configureComposer()
    {
        $this->info('Configuring composer.json...');

        $composerPath = base_path('composer.json');
        if (!$this->files->exists($composerPath)) {
            $this->error('composer.json not found');
            return;
        }

        try {
            $composer = json_decode($this->files->get($composerPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid composer.json format');
            }

            // Initialize extra.acorn.providers if it doesn't exist
            if (!isset($composer['extra'])) {
                $composer['extra'] = [];
            }
            if (!isset($composer['extra']['acorn'])) {
                $composer['extra']['acorn'] = [];
            }
            if (!isset($composer['extra']['acorn']['providers'])) {
                $composer['extra']['acorn']['providers'] = [];
            }

            // Add BonsaiServiceProvider if not already present
            $provider = 'Jackalopelabs\\BonsaiCli\\Providers\\BonsaiServiceProvider';
            if (!in_array($provider, $composer['extra']['acorn']['providers'])) {
                $composer['extra']['acorn']['providers'][] = $provider;
                
                // Save the updated composer.json with proper formatting
                $this->files->put(
                    $composerPath,
                    json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
                );
                
                $this->info('✓ Added BonsaiServiceProvider to composer.json');
            } else {
                $this->info('BonsaiServiceProvider already registered in composer.json');
            }
        } catch (\Exception $e) {
            $this->error('Failed to update composer.json: ' . $e->getMessage());
        }
    }
}