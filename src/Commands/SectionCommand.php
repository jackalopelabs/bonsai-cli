<?php

namespace JackalopeLabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SectionCommand extends Command
{
    protected $signature = 'bonsai:section {name} {--component=} {--template=}';
    protected $description = 'Create a new Bonsai section with dynamic component data';

    protected function getComponentSchema($componentName)
    {
        // Define component schemas with their expected data structure
        $schemas = [
            'hero' => [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero title',
                    'default' => 'Welcome to Our Site'
                ],
                'description' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero description',
                    'default' => 'Your journey starts here'
                ],
                'imagePath' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero image path (relative to assets, e.g. images/hero.jpg)',
                    'default' => 'images/hero.jpg'
                ],
                'cta' => [
                    'type' => 'object',
                    'schema' => [
                        'text' => [
                            'type' => 'string',
                            'prompt' => 'Enter CTA button text',
                            'default' => 'Learn More'
                        ],
                        'url' => [
                            'type' => 'string',
                            'prompt' => 'Enter CTA button URL',
                            'default' => '#'
                        ]
                    ]
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
                        $item[$subKey] = $this->ask(
                            sprintf("%s #%d: %s", 
                                Str::title($key), 
                                $i + 1, 
                                $subField['prompt']
                            )
                        );
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
        // Create a safe variable name for PHP
        $dataVarName = Str::camel($name) . 'Data';
        
        $template = <<<BLADE
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

<x-{$componentName}

BLADE;

        // Add props
        foreach ($data as $key => $value) {
            $template .= "    :{$key}=\"\${$dataVarName}['{$key}']\"\n";
        }

        $template .= "/>\n";

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
        $data = $this->promptForData($schema);

        // Generate Blade template
        $template = $this->generateBladeTemplate($name, $componentName, $data);

        // Save to file
        $path = resource_path("views/bonsai/sections/{$name}.blade.php");
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $template);

        $this->info("Section created successfully: {$path}");
        return 0;
    }
}