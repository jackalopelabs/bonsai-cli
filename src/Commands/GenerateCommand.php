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
            $this->copyComponentTemplate($componentName);

            if ($componentName === 'card') {
                $this->copyComponentIcon('flowchart');
            } else if ($componentName === 'widget') {
                $this->copyComponentTemplate('accordion');
                $this->copyComponentTemplate('cta');
                $this->copyComponentTemplate('list-item');
            } else if ($componentName === 'feature-grid') {
                $this->info("Installing feature-grid component...");
            }
        }
    }

    protected function copyComponentTemplate($componentName)
    {
        $possiblePaths = [
            base_path("templates/components/{$componentName}.blade.php"),
            base_path("templates/components/icons/{$componentName}.blade.php"),
            __DIR__ . "/../../templates/components/{$componentName}.blade.php",
            __DIR__ . "/../../templates/components/icons/{$componentName}.blade.php",
            base_path("resources/views/bonsai/components/{$componentName}.blade.php")
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $this->installComponentTemplate($path, $componentName);
                return;
            }
        }

        // If no template found, create a basic one
        $this->createBasicComponent($componentName);
    }

    protected function installComponentTemplate($templatePath, $componentName)
    {
        $isIcon = strpos($templatePath, '/icons/') !== false;
        $isSubComponent = in_array($componentName, ['accordion', 'cta', 'list-item']);

        $targetDir = match(true) {
            $isIcon => resource_path("views/bonsai/components/icons"),
            $isSubComponent => resource_path("views/bonsai/components"),
            default => resource_path("views/bonsai/components")
        };

        if (!$this->files->exists($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
        }

        $targetPath = "{$targetDir}/" . basename($templatePath);
        $this->files->copy($templatePath, $targetPath);
    }

    protected function createBasicComponent($name)
    {
        $targetPath = base_path("resources/views/bonsai/components/{$name}.blade.php");
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
        foreach ($sections as $section => $config) {
            $componentType = $config['component'] ?? $section;

            $sectionPath = resource_path("views/bonsai/sections/{$section}.blade.php");
            if (!$this->files->exists(dirname($sectionPath))) {
                $this->files->makeDirectory(dirname($sectionPath), 0755, true);
            }

            $sectionContent = $this->generateSectionContent($section, $componentType, $config['data'] ?? []);
            $this->files->put($sectionPath, $sectionContent);
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
                <span class="text-gray-400">{{ \${$dataVarName}['subtitle'] ?? 'Limited-time pricing available now' }}</span>
            </div>
            <h2 class="text-5xl font-bold text-gray-900 mb-4 pt-4">{{ \${$dataVarName}['title'] ?? 'Choose Your Plan' }}</h2>
            <p class="text-gray-500 mb-8">{{ \${$dataVarName}['description'] ?? 'Select the plan that best suits your needs. Lock in your price early and keep it forever, or until you cancel.' }}</p>
        </div>
    </div>

    @php
    \$boxes = \${$dataVarName}['pricingBoxes'] ?? [];
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
        $config = $this->loadConfig($this->getConfigPath($this->argument('template')));
        $themeSettings = $config['theme'] ?? [
            'body' => ['class' => 'bg-gray-100']
        ];

        foreach ($layouts as $layout => $layoutConfig) {
            $layoutPath = resource_path("views/bonsai/layouts/{$layout}.blade.php");
            if (!$this->files->exists(dirname($layoutPath))) {
                $this->files->makeDirectory(dirname($layoutPath), 0755, true);
            }

            $bodyClass = $themeSettings['body']['class'] ?? 'bg-gray-100';

            // Force update existing layout
            $this->info("Updating layout: {$layout}");
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
    <body @php(body_class("{$bodyClass}"))>
        @php(wp_body_open())
        <div id="app">
            <a class="sr-only focus:not-sr-only" href="#main">
                {{ __('Skip to content', 'radicle') }}
            </a>
            @include('bonsai.sections.site_header')
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

            // Always update the layout file
            $this->files->put($layoutPath, $layoutContent);
            $this->info("✓ Layout updated with theme settings: {$layoutPath}");
        }
    }

    protected function generateSitePages($pages)
    {
        foreach ($pages as $slug => $config) {
            $title = $config['title'] ?? Str::title($slug);
            $layout = $config['layout'] ?? 'default';

            $templateContent = $this->generateTemplateContent($slug, $layout, $config);
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

            if (!is_wp_error($pageId) && !empty($config['is_homepage'])) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $pageId);
            }
        }
    }

    protected function generateTemplateContent($template, $layout, $config)
    {
        $layoutSections = $this->getLayoutSections($layout);
        $contentSections = array_filter($layoutSections, fn($section) => $section !== 'site_header');

        $sectionIncludes = collect($contentSections)
            ->map(fn($section) => "@include('bonsai.sections.{$section}')")
            ->implode("\n    ");

        $templateName = ucfirst($template);

        return <<<BLADE
{{--
    Template Name: {$templateName} Template
--}}
@extends('bonsai.layouts.{$layout}')

@section('content')
    {$sectionIncludes}
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
}
