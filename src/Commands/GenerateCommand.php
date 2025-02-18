<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Jackalopelabs\BonsaiCli\Traits\HandlesTemplatePaths;

class GenerateCommand extends Command
{
    use HandlesTemplatePaths;

    protected $signature = 'bonsai:generate {template} {--config=}';
    protected $description = 'Generate a complete Bonsai site from a template configuration';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);

        $this->info("🌳 Starting Bonsai generation for template: {$template}");

        $config = $this->loadConfig($configPath);
        $hasHeroicons = $this->checkHeroiconsSetup();

        $this->generateComponents($config['components'] ?? [], $hasHeroicons);
        $this->generateSections($config['sections'] ?? []);
        $this->generateLayouts($config['layouts'] ?? []);
        $this->generateSitePages($config['pages'] ?? []);
        $this->generateDatabase($config['database'] ?? []);
        $this->configureSettings($config['settings'] ?? []);

        $this->displaySuccessMessage($template);
        return 0;
    }

    protected function getConfigPath($template)
    {
        $rootPath = $this->getLaravel()->basePath();
        $paths = [
            "{$rootPath}/config/bonsai/templates/{$template}.yml",
            "{$rootPath}/config/bonsai/{$template}.yml",
            "{$rootPath}/config/templates/{$template}.yml",
            __DIR__ . "/../../config/templates/{$template}.yml"
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \Exception("Configuration file not found for template: {$template}");
    }

    protected function loadConfig($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("Configuration file not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new \Exception("Configuration file is not readable: {$path}");
        }

        $contents = file_get_contents($path);
        return Yaml::parse($contents);
    }

    protected function generateComponents($components, $hasHeroicons = false)
    {
        putenv("BONSAI_HAS_HEROICONS=" . ($hasHeroicons ? "true" : "false"));
        $template = $this->argument('template');

        if (isset($components[0])) {
            $components = array_filter($components, function($c) {
                return in_array($c, [
                    'hero','header','card','widget','accordion',
                    'cta','list-item','pricing-box','feature-grid'
                ]);
            });
            $components = array_combine($components, array_fill(0, count($components), []));
        }

        foreach ($components as $component => $config) {
            $componentName = is_array($config) ? $component : $config;
            
            // First try to copy from template-specific components
            if (!$this->copyTemplateComponent($componentName)) {
                // If not found, fall back to core Bonsai components
                $this->copyBonsaiComponent($componentName);
            }

            if ($componentName === 'card') {
                $this->copyComponentIcon('flowchart');
            } else if ($componentName === 'widget') {
                // For widget dependencies, we want them in the core bonsai components
                $this->copyBonsaiComponent('accordion');
                $this->copyBonsaiComponent('cta');
                $this->copyBonsaiComponent('list-item');
            }
        }
    }

    protected function copyTemplateComponent($componentName)
    {
        $template = $this->argument('template');
        $possiblePaths = [
            base_path("templates/{$template}/components/{$componentName}.blade.php"),
            __DIR__ . "/../../templates/{$template}/components/{$componentName}.blade.php"
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $targetDir = resource_path("views/{$template}/components");
                if (!$this->files->exists($targetDir)) {
                    $this->files->makeDirectory($targetDir, 0755, true);
                }
                
                $targetPath = "{$targetDir}/{$componentName}.blade.php";
                $this->files->copy($path, $targetPath);
                return true;
            }
        }

        return false;
    }

    protected function copyBonsaiComponent($componentName)
    {
        $possiblePaths = [
            base_path("templates/components/{$componentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$componentName}.blade.php",
            base_path("resources/views/bonsai/components/{$componentName}.blade.php")
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $targetDir = resource_path("views/bonsai/components");
                if (!$this->files->exists($targetDir)) {
                    $this->files->makeDirectory($targetDir, 0755, true);
                }
                
                $targetPath = "{$targetDir}/{$componentName}.blade.php";
                $this->files->copy($path, $targetPath);
                
                // Also register the component in the service provider
                $this->registerBonsaiComponent($componentName);
                return true;
            }
        }

        // If no template found, create a basic one in bonsai components
        $this->createBasicComponent($componentName);
        $this->registerBonsaiComponent($componentName);
        return true;
    }

    protected function registerBonsaiComponent($componentName)
    {
        // This ensures the component is registered with the bonsai:: namespace
        $providerPath = app_path('Providers/ViewServiceProvider.php');
        if (!file_exists($providerPath)) {
            return;
        }

        $content = file_get_contents($providerPath);
        $componentLine = "Blade::component('bonsai.components.{$componentName}', 'bonsai::{$componentName}');";
        
        if (strpos($content, $componentLine) === false) {
            // Find the boot method
            if (preg_match('/public function boot\(\)\s*{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1] + strlen($matches[0][0]);
                $content = substr_replace($content, "\n        " . $componentLine, $position, 0);
                file_put_contents($providerPath, $content);
            }
        }
    }

    protected function createBasicComponent($name)
    {
        $targetPath = resource_path("views/bonsai/components/{$name}.blade.php");
        $content = <<<BLADE
<div class="component-{$name}">
    <div class="p-4">
        <h2>{{ \$title ?? 'Default Title' }}</h2>
        {{ \$slot }}
    </div>
</div>
BLADE;

        $this->files->put($targetPath, $content);
    }

    protected function generateSections($sections)
    {
        $template = $this->argument('template');
        
        foreach ($sections as $section => $config) {
            $componentType = $config['component'] ?? $section;
            $type = explode('_', $section)[0];
            
            // Generate the section in the template's directory
            $fullPath = resource_path("views/{$template}/sections/{$section}.blade.php");
            
            if (!$this->files->exists(dirname($fullPath))) {
                $this->files->makeDirectory(dirname($fullPath), 0755, true);
            }

            $sectionContent = $this->generateSectionContent($section, $componentType, $config['data'] ?? []);
            $this->files->put($fullPath, $sectionContent);
            
            $this->info("Generated section: {$template}/sections/{$section}");
        }
    }

    protected function generateSectionContent($section, $componentType, $data)
    {
        $dataVarName = "{$section}Data";

        $dataLines = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $arrayStr = $this->arrayToPhpString($value, 1);
                $dataLines[] = "    '{$key}' => {$arrayStr},";
            } else {
                $dataLines[] = "    '{$key}' => " . var_export($value, true) . ",";
            }
        }

        $template = <<<BLADE
@props([
    'class' => ''
])

@php
\${$dataVarName} = [
BLADE;

        $template .= implode("\n", $dataLines) . "\n];\n@endphp\n\n";

        // If component is pricing-box, output the multi-box snippet
        if ($componentType === 'pricing-box') {
            $template .= <<<BLADE
<section class="py-24" id="plans">
    <div class="py-12">
        <div class="mx-auto px-4 text-center">
            <div class="inline-flex items-center gap-2 rounded-md bg-white text-sm px-3 py-1 text-center mb-4">
                <x-heroicon-s-calendar-days class="h-6 w-6" />
                <span class="text-gray-400">@{{ isset(\${$dataVarName}['subtitle']) ? \${$dataVarName}['subtitle'] : 'Limited-time pricing available now' }}</span>
            </div>
            <h2 class="text-5xl font-bold text-gray-900 mb-4 pt-4">@{{ isset(\${$dataVarName}['title']) ? \${$dataVarName}['title'] : 'Choose Your Plan' }}</h2>
            <p class="text-gray-500 mb-8">@{{ isset(\${$dataVarName}['description']) ? \${$dataVarName}['description'] : 'Select the plan that best suits your needs. Lock in your price early and keep it forever, or until you cancel.' }}</p>
        </div>
    </div>

    @php
    \$boxes = isset(\${$dataVarName}['pricingBoxes']) ? \${$dataVarName}['pricingBoxes'] : [];
    @endphp

    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-center items-start space-y-8 md:space-y-0 md:space-x-8">
            @foreach (\$boxes as \$box)
                <x-bonsai::pricing-box 
                    :icon="\$box['icon']"
                    :iconColor="\$box['iconColor']"
                    :planType="\$box['planType']"
                    :price="\$box['price']"
                    :features="\$box['features']"
                    :ctaLink="\$box['ctaLink']"
                    :ctaText="\$box['ctaText']"
                    :ctaColor="\$box['ctaColor']"
                    :iconBtn="\$box['iconBtn']"
                    :iconBtnColor="\$box['iconBtnColor']"
                />
            @endforeach
        </div>
    </div>
</section>
BLADE;
        } else {
            // Default scenario
            $template .= <<<BLADE
<div class="{{ \$class }}">
    <x-bonsai::{$componentType} :data="\${$dataVarName}" />
</div>
BLADE;
        }

        return $template;
    }

    protected function arrayToPhpString($array, $depth = 0)
    {
        $indent = str_repeat('    ', $depth);
        $output = "[\n";
        foreach ($array as $key => $value) {
            $output .= $indent . "    ";
            if (is_string($key)) {
                $output .= "'{$key}' => ";
            }

            if (is_array($value)) {
                $output .= $this->arrayToPhpString($value, $depth + 1);
            } else {
                $output .= "'" . addslashes($value) . "'";
            }
            $output .= ",\n";
        }
        $output .= $indent . "]";
        return $output;
    }

    protected function generateLayouts($layouts)
    {
        $template = $this->argument('template');
        $config = $this->loadConfig($this->getConfigPath($template));
        $themeSettings = $config['theme'] ?? [
            'body' => ['class' => 'bg-gray-100']
        ];

        foreach ($layouts as $layout => $layoutConfig) {
            $layoutPath = resource_path("views/{$template}/layouts/{$layout}.blade.php");
            if (!$this->files->exists(dirname($layoutPath))) {
                $this->files->makeDirectory(dirname($layoutPath), 0755, true);
            }

            $layoutContent = <<<BLADE
<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @include('utils.styles')
    </head>
    <body @php(body_class())>
        @php(wp_body_open())
        <div id="app" class="{{ \$themeSettings['body']['class'] ?? 'bg-gray-100' }}">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>
            @include('{$template}.sections.site_header')
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

            $this->files->put($layoutPath, $layoutContent);
        }
    }

    protected function generateSitePages($pages)
    {
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        // If no pages defined, create a default page using the template
        if (empty($pages)) {
            $pages = [
                $template => [
                    'title' => ucfirst($template),
                    'layout' => $template,
                    'is_homepage' => true,
                    'sections' => array_keys($config['sections'] ?? [])
                ]
            ];
        }

        foreach ($pages as $slug => $pageConfig) {
            $title = $pageConfig['title'] ?? Str::title($slug);
            $layout = $pageConfig['layout'] ?? $template;
            $sections = $pageConfig['sections'] ?? array_keys($config['sections'] ?? []);

            $templateContent = $this->generateTemplateContent($template, $layout, ['sections' => $sections]);
            $templatePath = resource_path("views/bonsai/templates/template-{$layout}.blade.php");

            if (!$this->files->exists(dirname($templatePath))) {
                $this->files->makeDirectory(dirname($templatePath), 0755, true);
            }

            $this->files->put($templatePath, $templateContent);

            $pageId = wp_insert_post([
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => [
                    '_wp_page_template' => "template-{$layout}.blade.php",
                    '_bonsai_generated' => 'true',
                    '_bonsai_template'  => $layout,
                ],
            ]);

            if (!is_wp_error($pageId) && !empty($pageConfig['is_homepage'])) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $pageId);
            }
        }
    }

    protected function generateTemplateContent($template, $layout, $config)
    {
        $sections = $config['sections'] ?? [];
        $sectionIncludes = array_map(function($section) use ($template) {
            return "@include('{$template}.sections.{$section}')";
        }, $sections);

        return <<<BLADE
{{-- 
    Template Name: {{ \$config['name'] ?? ucfirst(\$template) }}
--}}
@extends('{$template}.layouts.{$layout}')

@section('content')
{$this->indent(implode("\n", $sectionIncludes), 4)}
@endsection
BLADE;
    }

    protected function getLayoutSections($layoutName)
    {
        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        return $config['layouts'][$layoutName]['sections'] ?? [];
    }

    protected function generateDatabase($database)
    {
        if (empty($database)) return;

        if (!empty($database['seeds'])) {
            foreach ($database['seeds'] as $seeder) {
                if (class_exists("Database\\Seeders\\{$seeder}")) {
                    $this->call('db:seed', ['--class' => $seeder]);
                }
            }
        }

        if (!empty($database['imports'])) {
            foreach ($database['imports'] as $import) {
                if (str_ends_with($import, '.sql') && file_exists($import)) {
                    $this->importSqlFile($import);
                }
            }
        }
    }

    protected function configureSettings($settings)
    {
        if (empty($settings)) return;

        $template = $this->argument('template');
        $configPath = $this->option('config') ?? $this->getConfigPath($template);
        $config = $this->loadConfig($configPath);

        if (isset($config['name'])) {
            update_option('blogname', $config['name']);
        }

        foreach ($settings['options'] ?? [] as $option => $value) {
            if (in_array($option, ['template', 'stylesheet', 'current_theme']) ||
                str_starts_with($option, 'theme_mods_')) {
                continue;
            }
            update_option($option, $value);
        }

        if (!empty($settings['env'])) {
            $this->updateEnvFile($settings['env']);
        }

        if (!empty($settings['api_keys'])) {
            $this->storeApiKeys($settings['api_keys']);
        }
    }

    protected function updateEnvFile($envVars)
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return;

        $envContent = file_get_contents($envPath);

        foreach ($envVars as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    protected function storeApiKeys($apiKeys)
    {
        foreach ($apiKeys as $service => $keys) {
            $this->updateEnvFile($keys);
        }
    }

    protected function copyComponentIcon($iconName)
    {
        $sourcePath = __DIR__ . "/../../templates/components/icons/{$iconName}.blade.php";
        if (!file_exists($sourcePath)) return;

        $targetDir = resource_path("views/bonsai/components/icons");
        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
        }

        $targetPath = "{$targetDir}/{$iconName}.blade.php";
        $this->files->copy($sourcePath, $targetPath);
    }

    protected function checkHeroiconsSetup()
    {
        return class_exists(\BladeUI\Icons\BladeIconsServiceProvider::class) &&
               class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class);
    }

    protected function displaySuccessMessage($template)
    {
        $this->info("🌳 Successfully generated {$template} template! Run `npm run dev` to compile assets.");
    }

    protected function importSqlFile($file)
    {
        // Implement if needed
    }

    protected function indent($content, $spaces = 4)
    {
        $lines = explode("\n", $content);
        $indented = array_map(function($line) use ($spaces) {
            return str_repeat(' ', $spaces) . $line;
        }, $lines);
        return implode("\n", $indented);
    }
}
