<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class SectionCommand extends Command
{
    protected $signature = 'bonsai:section {name} {--component=} {--template=} {--default : Use default configuration without prompting}';
    protected $description = 'Create a new Bonsai section with dynamic component data';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    protected function getDefaultFaqs()
    {
        return [
            [
                'question' => 'What services do you offer?',
                'answer' => 'We offer a comprehensive range of digital solutions including web development, design, and digital marketing strategies tailored to your business needs.'
            ],
            [
                'question' => 'How long does a typical project take?',
                'answer' => 'Project timelines vary based on complexity and scope. A typical website project takes 6-8 weeks from concept to launch, while smaller projects might take 2-4 weeks.'
            ],
            [
                'question' => 'Do you provide ongoing support?',
                'answer' => 'Yes, we offer various support and maintenance packages to ensure your digital presence remains optimal after launch.'
            ],
            [
                'question' => 'What is your pricing structure?',
                'answer' => 'We provide customized quotes based on your specific requirements. Each project is unique, and we will work with you to find a solution that fits your budget.'
            ],
            [
                'question' => 'Can you help with existing websites?',
                'answer' => 'Absolutely! We can help optimize, update, or completely redesign existing websites to improve their performance and user experience.'
            ]
        ];
    }

    protected function getComponentSchema($componentName)
    {
        $template = getenv('BONSAI_TEMPLATE') ?: 'bonsai';

        $possiblePaths = [
            base_path("config/bonsai/templates/{$template}.yml"),
            base_path("config/bonsai/{$template}.yml"),
            base_path("config/templates/{$template}.yml"),
            __DIR__ . "/../../config/templates/{$template}.yml"
        ];

        $configPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $configPath = $path;
                break;
            }
        }

        if (!$configPath) {
            return [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter title',
                    'default' => 'Default Title'
                ],
                'description' => [
                    'type' => 'string',
                    'prompt' => 'Enter description',
                    'default' => 'Default description'
                ]
            ];
        }

        try {
            $config = Yaml::parseFile($configPath);
            $sections = $config['sections'] ?? [];
            foreach ($sections as $sectionName => $sectionConfig) {
                if (isset($sectionConfig['component']) && $sectionConfig['component'] === $componentName) {
                    $schema = [];
                    foreach ($sectionConfig['data'] as $key => $value) {
                        $schema[$key] = [
                            'type' => is_array($value) ? 'array' : 'string',
                            'prompt' => "Enter {$key}",
                            'default' => $value
                        ];
                    }
                    return $schema;
                }
            }
        } catch (\Exception $e) {
            $this->error("Error loading template configuration: " . $e->getMessage());
        }

        if ($componentName === 'pricing-box') {
            return [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter section title',
                    'default' => 'Choose Your Plan'
                ],
                'subtitle' => [
                    'type' => 'string',
                    'prompt' => 'Enter subtitle',
                    'default' => 'Limited-time pricing available now'
                ],
                'description' => [
                    'type' => 'string',
                    'prompt' => 'Enter description',
                    'default' => 'Select the plan that best suits your needs.'
                ],
                'pricingBoxes' => [
                    'type' => 'array',
                    'prompt' => 'How many pricing boxes?',
                    'default' => 3,
                    'schema' => [
                        'icon' => [
                            'type' => 'string',
                            'prompt' => 'Enter icon name (e.g., heroicon-o-command-line)',
                            'default' => 'heroicon-o-command-line'
                        ],
                        'iconColor' => [
                            'type' => 'string',
                            'prompt' => 'Enter icon color class',
                            'default' => 'text-gray-400'
                        ],
                        'planType' => [
                            'type' => 'string',
                            'prompt' => 'Enter plan type',
                            'default' => 'Basic'
                        ],
                        'price' => [
                            'type' => 'string',
                            'prompt' => 'Enter price',
                            'default' => 'Free'
                        ],
                        'features' => [
                            'type' => 'array',
                            'prompt' => 'How many features?',
                            'default' => 3,
                            'schema' => [
                                'feature' => [
                                    'type' => 'string',
                                    'prompt' => 'Enter feature',
                                    'default' => 'Feature item'
                                ]
                            ]
                        ],
                        'ctaLink' => [
                            'type' => 'string',
                            'prompt' => 'Enter CTA link',
                            'default' => '#'
                        ],
                        'ctaText' => [
                            'type' => 'string',
                            'prompt' => 'Enter CTA text',
                            'default' => 'Get Started'
                        ],
                        'ctaColor' => [
                            'type' => 'string',
                            'prompt' => 'Enter CTA color class',
                            'default' => 'bg-white'
                        ],
                        'iconBtn' => [
                            'type' => 'string',
                            'prompt' => 'Enter button icon name',
                            'default' => 'heroicon-o-arrow-right'
                        ],
                        'iconBtnColor' => [
                            'type' => 'string',
                            'prompt' => 'Enter button icon color class',
                            'default' => 'text-gray-500'
                        ]
                    ]
                ]
            ];
        }

        return [
            'title' => [
                'type' => 'string',
                'prompt' => 'Enter title',
                'default' => 'Default Title'
            ],
            'description' => [
                'type' => 'string',
                'prompt' => 'Enter description',
                'default' => 'Default description'
            ]
        ];
    }

    protected function promptForData($schema)
    {
        $data = [];

        foreach ($schema as $key => $field) {
            if ($field['type'] === 'string') {
                $data[$key] = $this->ask($field['prompt'], $field['default'] ?? null);
            } elseif ($field['type'] === 'array') {
                if ($key === 'faqs') {
                    $useDefaults = !$this->ask('Would you like to enter custom FAQs? (yes/no)', 'no');
                    if ($useDefaults) {
                        $data[$key] = $this->getDefaultFaqs();
                        $this->info('Using default FAQs.');
                    } else {
                        $count = (int) $this->ask($field['prompt'], $field['default'] ?? 3);
                        $data[$key] = [];

                        $this->info("\nEntering details for {$count} items:");

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

                            if (!empty($item['question']) && !empty($item['answer'])) {
                                $data[$key][] = $item;
                            }
                        }

                        if (empty($data[$key])) {
                            $data[$key] = $this->getDefaultFaqs();
                            $this->info('No valid FAQs entered. Using default FAQs.');
                        }
                    }
                } else {
                    $count = (int) $this->ask($field['prompt'] ?? 'How many items?', $field['default'] ?? 3);
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

        // Build the PHP array for data
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
        $template .= "<div class=\"{{ \$class }}\">\n";
        $template .= "    <x-bonsai::{$componentName} :data=\"\${$dataVarName}\" />\n";
        $template .= "</div>\n";

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
        $useDefault = $this->option('default');
        
        // Get component schema
        $schema = $this->getComponentSchema($componentName);
        if (empty($schema)) {
            $this->error("No schema found for component: {$componentName}");
            return 1;
        }

        // Get data from environment variables or prompt
        $data = [];
        foreach ($schema as $key => $field) {
            $envKey = "BONSAI_DATA_{$key}";
            $envValue = getenv($envKey);
            
            if ($envValue !== false) {
                if ($field['type'] === 'array') {
                    $decoded = json_decode($envValue, true);
                    $data[$key] = $decoded !== null ? $decoded : $field['default'];
                } else {
                    $data[$key] = $envValue;
                }
                $this->info("Using environment data for {$key}: {$envValue}");
            } else {
                if ($useDefault) {
                    $data[$key] = $field['default'];
                    $this->info("Using default value for {$key}");
                } else {
                    if ($field['type'] === 'array') {
                        $data[$key] = $field['default'];
                    } else {
                        $data[$key] = $this->ask($field['prompt'], $field['default']);
                    }
                }
            }
        }

        // Generate Blade template
        $template = $this->generateBladeTemplate($name, $componentName, $data);

        // Create directories if they don't exist
        $directory = resource_path('views/bonsai/sections');
        $this->createDirectory($directory);

        // Save to file
        $path = "{$directory}/{$name}.blade.php";
        $this->files->put($path, $template);

        $this->info("✓ Section created successfully: {$path}");
        
        return 0;
    }

    protected function getDefaultData($schema)
    {
        $data = [];
        foreach ($schema as $key => $field) {
            if ($field['type'] === 'string') {
                $data[$key] = $field['default'] ?? '';
            } elseif ($field['type'] === 'array') {
                if ($key === 'faqs') {
                    $data[$key] = $this->getDefaultFaqs();
                } elseif (isset($field['schema'])) {
                    $data[$key] = [];
                    $count = $field['default'] ?? 3;
                    for ($i = 0; $i < $count; $i++) {
                        $item = [];
                        foreach ($field['schema'] as $subKey => $subField) {
                            $item[$subKey] = $subField['default'] ?? '';
                        }
                        $data[$key][] = $item;
                    }
                } else {
                    $data[$key] = $field['default'] ?? [];
                }
            } elseif ($field['type'] === 'boolean') {
                $data[$key] = $field['default'] ?? false;
            } elseif ($field['type'] === 'object' && isset($field['schema'])) {
                $data[$key] = [];
                foreach ($field['schema'] as $subKey => $subField) {
                    $data[$key][$subKey] = $subField['default'] ?? '';
                }
            }
        }
        return $data;
    }

    protected function generateHeaderSection($name)
    {
        // This method remains for backward compatibility, you can remove or refactor similarly if needed.
        $siteName = getenv('BONSAI_DATA_siteName') ?: 'Cypress';
        $iconComponent = getenv('BONSAI_DATA_iconComponent') ?: 'icon-bonsai';
        $navLinks = json_decode(getenv('BONSAI_DATA_navLinks'), true) ?: [
            ['url' => '#features', 'label' => 'Features'],
            ['url' => '#pricing', 'label' => 'Pricing'],
            ['url' => '#faq', 'label' => 'FAQ'],
        ];
        $primaryLink = getenv('BONSAI_DATA_primaryLink') ?: '#signup';
        $containerClasses = getenv('BONSAI_DATA_containerClasses') ?: 'max-w-5xl mx-auto';
        $containerInnerClasses = getenv('BONSAI_DATA_containerInnerClasses') ?: 'px-6';

        $content = <<<BLADE
@props([
    'class' => ''
])

@php
\$headerData = [
    'siteName' => '{$siteName}',
    'iconComponent' => '{$iconComponent}',
    'navLinks' => {$this->arrayToPhpString($navLinks)},
    'primaryLink' => '{$primaryLink}',
    'containerClasses' => '{$containerClasses}',
    'containerInnerClasses' => '{$containerInnerClasses}',
];
@endphp

<div class="{{ \$class }}">
    <x-bonsai::header :data="\$headerData"/>
</div>
BLADE;

        return $content;
    }
}
