<?php

namespace JackalopeLabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class SectionCommand extends Command
{
    protected $signature = 'bonsai:section {name} {--component=} {--template=}';
    protected $description = 'Create a new Bonsai section with dynamic component data';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    protected function getComponentSchema($componentName)
    {
        $schemas = [
            'hero' => [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero title',
                    'default' => 'Welcome to Our Site'
                ],
                'subtitle' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero subtitle',
                    'default' => 'Discover what makes us unique'
                ],
                'imagePath' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero image path (relative to assets)',
                    'default' => 'images/hero.jpg'
                ],
                'l1' => [
                    'type' => 'string',
                    'prompt' => 'Enter first list item',
                    'default' => 'Feature one description'
                ],
                'l2' => [
                    'type' => 'string',
                    'prompt' => 'Enter second list item',
                    'default' => 'Feature two description'
                ],
                'l3' => [
                    'type' => 'string',
                    'prompt' => 'Enter third list item',
                    'default' => 'Feature three description'
                ],
                'l4' => [
                    'type' => 'string',
                    'prompt' => 'Enter fourth list item',
                    'default' => 'Feature four description'
                ],
                'primaryText' => [
                    'type' => 'string',
                    'prompt' => 'Enter primary button text',
                    'default' => 'Get Started'
                ],
                'primaryLink' => [
                    'type' => 'string',
                    'prompt' => 'Enter primary button link target',
                    'default' => '#features'
                ],
                'secondaryText' => [
                    'type' => 'string',
                    'prompt' => 'Enter secondary button text',
                    'default' => 'Watch Video'
                ]
            ],
            'faq' => [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter FAQ section title',
                    'default' => 'Frequently Asked Questions'
                ],
                'items' => [
                    'type' => 'array',
                    'prompt' => 'How many FAQ items?',
                    'schema' => [
                        'question' => [
                            'type' => 'string',
                            'prompt' => 'Enter question'
                        ],
                        'answer' => [
                            'type' => 'string',
                            'prompt' => 'Enter answer'
                        ]
                    ]
                ]
            ]
        ];

        return $schemas[$componentName] ?? [];
    }

    protected function promptForData($schema)
    {
        $data = [];

        foreach ($schema as $key => $field) {
            if ($field['type'] === 'string') {
                $data[$key] = $this->ask(
                    $field['prompt'],
                    $field['default'] ?? null
                );
            } elseif ($field['type'] === 'array') {
                $count = (int) $this->ask($field['prompt']);
                $data[$key] = [];
                
                for ($i = 0; $i < $count; $i++) {
                    $item = [];
                    foreach ($field['schema'] as $subKey => $subField) {
                        $prompt = sprintf(
                            "%s #%d: %s", 
                            Str::title($key), 
                            $i + 1, 
                            $subField['prompt']
                        );
                        $item[$subKey] = $this->ask($prompt);
                    }
                    $data[$key][] = $item;
                }
            } elseif ($field['type'] === 'object') {
                $data[$key] = [];
                foreach ($field['schema'] as $subKey => $subField) {
                    $data[$key][$subKey] = $this->ask(
                        $subField['prompt'],
                        $subField['default'] ?? null
                    );
                }
            }
        }

        return $data;
    }

    protected function generateBladeTemplate($name, $componentName, $data)
    {
        $dataVarName = Str::camel($name) . 'Data';
        
        $template = <<<BLADE
@props([
    'class' => ''
])

@php
\${$dataVarName} = [

BLADE;

        // Handle each data field
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $template .= "    '{$key}' => " . $this->arrayToPhpString($value, 1) . ",\n";
            } else {
                $template .= "    '{$key}' => '" . addslashes($value) . "',\n";
            }
        }

        $template .= <<<BLADE
];
@endphp

<div class="{{ \$class }}">
    <x-{$componentName}

BLADE;

        // Add props
        foreach ($data as $key => $value) {
            $template .= "        :{$key}=\"\${$dataVarName}['{$key}']\"\n";
        }

        $template .= "    />\n</div>\n";

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

    protected function createDirectory($path)
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    public function handle()
    {
        $name = $this->argument('name');
        $componentName = $this->option('component') ?? $name;
        
        // Get component schema
        $schema = $this->getComponentSchema($componentName);
        if (empty($schema)) {
            $this->error("No schema found for component: {$componentName}");
            return 1;
        }

        // Collect data through CLI prompts
        $this->info("Configuring {$componentName} section...");
        $data = $this->promptForData($schema);

        // Generate Blade template
        $template = $this->generateBladeTemplate($name, $componentName, $data);

        // Create directories if they don't exist
        $directory = resource_path('views/bonsai/sections');
        $this->createDirectory($directory);

        // Save to file
        $path = "{$directory}/{$name}.blade.php";
        $this->files->put($path, $template);

        $this->info("✓ Section created successfully: {$path}");
        
        // Show next steps
        $this->info("\nNext steps:");
        $this->line(" - Review the generated section at: {$path}");
        $this->line(" - Include it in your layout using: @include('bonsai.sections.{$name}')");
        $this->line(" - Customize the section's appearance by passing a class prop");
        
        return 0;
    }
}