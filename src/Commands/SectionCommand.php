<?php

namespace JackalopeLabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

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
        $schemas = [
            'hero' => [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero title',
                    'default' => 'Welcome to Cypress'
                ],
                'subtitle' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero subtitle',
                    'default' => 'Modern Solutions for Modern Businesses'
                ],
                'description' => [
                    'type' => 'string',
                    'prompt' => 'Enter hero description',
                    'default' => 'Empowering your business with cutting-edge solutions'
                ],
                'imagePaths' => [
                    'type' => 'array',
                    'prompt' => 'Enter image paths (comma-separated)',
                    'default' => ['images/hero-main.jpg']
                ],
                'buttonText' => [
                    'type' => 'string',
                    'prompt' => 'Enter primary button text',
                    'default' => 'Get Started'
                ],
                'buttonLink' => [
                    'type' => 'string',
                    'prompt' => 'Enter primary button link',
                    'default' => '#contact'
                ],
                'secondaryText' => [
                    'type' => 'string',
                    'prompt' => 'Enter secondary button text',
                    'default' => 'Watch Demo'
                ],
                'secondaryLink' => [
                    'type' => 'string',
                    'prompt' => 'Enter secondary button link',
                    'default' => '#demo'
                ],
                'buttonLinkIcon' => [
                    'type' => 'boolean',
                    'prompt' => 'Show button link icon? (yes/no)',
                    'default' => true
                ],
                'secondaryIcon' => [
                    'type' => 'boolean',
                    'prompt' => 'Show secondary icon? (yes/no)',
                    'default' => true
                ]
            ],
            'slideshow' => [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter section title',
                    'default' => 'Latest News & Announcements'
                ],
                'announcements' => [
                    'type' => 'array',
                    'prompt' => 'How many announcements?',
                    'default' => 3,
                    'schema' => [
                        'title' => [
                            'type' => 'string',
                            'prompt' => 'Enter announcement title'
                        ],
                        'description' => [
                            'type' => 'string',
                            'prompt' => 'Enter announcement description'
                        ],
                        'link' => [
                            'type' => 'string',
                            'prompt' => 'Enter announcement link'
                        ],
                        'cta' => [
                            'type' => 'string',
                            'prompt' => 'Enter call to action text',
                            'default' => 'Learn More'
                        ],
                        'category' => [
                            'type' => 'string',
                            'prompt' => 'Enter announcement category',
                            'default' => 'News'
                        ],
                        'isNew' => [
                            'type' => 'boolean',
                            'prompt' => 'Is this a new announcement? (yes/no)',
                            'default' => 'no'
                        ],
                        'backgroundImage' => [
                            'type' => 'string',
                            'prompt' => 'Enter background image path',
                            'default' => 'images/announcement-bg.jpg'
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
                'faqs' => [
                    'type' => 'array',
                    'prompt' => 'How many FAQ items?',
                    'default' => 3,
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
            ],
            'widget' => [
                'items' => [
                    'type' => 'array',
                    'prompt' => 'How many widget items?',
                    'default' => 3,
                    'schema' => [
                        'id' => [
                            'type' => 'string',
                            'prompt' => 'Enter item ID (e.g., widget-1)',
                            'default' => 'widget-'
                        ],
                        'title' => [
                            'type' => 'string',
                            'prompt' => 'Enter item title'
                        ],
                        'content' => [
                            'type' => 'string',
                            'prompt' => 'Enter item content'
                        ],
                        'icon' => [
                            'type' => 'string',
                            'prompt' => 'Enter Heroicon component name (e.g., heroicon-o-check)',
                            'default' => 'heroicon-o-check'
                        ],
                        'cta' => [
                            'type' => 'object',
                            'schema' => [
                                'title' => [
                                    'type' => 'string',
                                    'prompt' => 'Enter CTA title'
                                ],
                                'link' => [
                                    'type' => 'string',
                                    'prompt' => 'Enter CTA link'
                                ],
                                'imagePath' => [
                                    'type' => 'string',
                                    'prompt' => 'Enter CTA image path'
                                ]
                            ]
                        ],
                        'listItems' => [
                            'type' => 'array',
                            'prompt' => 'How many list items?',
                            'schema' => [
                                'number' => [
                                    'type' => 'string',
                                    'prompt' => 'Enter item number'
                                ],
                                'itemName' => [
                                    'type' => 'string',
                                    'prompt' => 'Enter item name'
                                ],
                                'text' => [
                                    'type' => 'string',
                                    'prompt' => 'Enter item text'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'card-featured' => [
                'title' => [
                    'type' => 'string',
                    'prompt' => 'Enter card title'
                ],
                'subtitle' => [
                    'type' => 'array',
                    'prompt' => 'How many features?',
                    'schema' => [
                        'title' => [
                            'type' => 'string',
                            'prompt' => 'Enter feature title'
                        ],
                        'description' => [
                            'type' => 'string',
                            'prompt' => 'Enter feature description'
                        ],
                        'icon' => [
                            'type' => 'string',
                            'prompt' => 'Enter Heroicon component name',
                            'default' => 'heroicon-o-check'
                        ]
                    ]
                ],
                'items' => [
                    'type' => 'array',
                    'prompt' => 'How many items?',
                    'schema' => [
                        'title' => [
                            'type' => 'string',
                            'prompt' => 'Enter item title'
                        ]
                    ]
                ],
                'imagePath' => [
                    'type' => 'string',
                    'prompt' => 'Enter image path',
                    'default' => 'images/featured.jpg'
                ],
                'playIcon' => [
                    'type' => 'boolean',
                    'prompt' => 'Show play icon? (yes/no)',
                    'default' => 'no'
                ]
            ],
            'table' => [
                'products' => [
                    'type' => 'array',
                    'prompt' => 'How many products?',
                    'schema' => [
                        'name' => [
                            'type' => 'string',
                            'prompt' => 'Enter product name'
                        ],
                        'ref' => [
                            'type' => 'string',
                            'prompt' => 'Enter product reference'
                        ],
                        'dimensions' => [
                            'type' => 'string',
                            'prompt' => 'Enter dimensions'
                        ],
                        'woundPadSize' => [
                            'type' => 'string',
                            'prompt' => 'Enter wound pad size'
                        ],
                        'quantityPerBox' => [
                            'type' => 'string',
                            'prompt' => 'Enter quantity per box'
                        ],
                        'hcpcs' => [
                            'type' => 'string',
                            'prompt' => 'Enter HCPCS code'
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
                            
                            // Only add the item if both question and answer are provided
                            if (!empty($item['question']) && !empty($item['answer'])) {
                                $data[$key][] = $item;
                            }
                        }
                        
                        // If no valid FAQs were entered, use defaults
                        if (empty($data[$key])) {
                            $data[$key] = $this->getDefaultFaqs();
                            $this->info('No valid FAQs entered. Using default FAQs.');
                        }
                    }
                } else {
                    // Handle other array types if needed
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
                $arrayStr = $this->arrayToPhpString($value, 1);
                $template .= "    '{$key}' => " . $arrayStr . ",\n";
            } else {
                $template .= "    '{$key}' => " . var_export($value, true) . ",\n";
            }
        }

        $template .= <<<BLADE
];
@endphp

<div class="{{ \$class }}">
    <x-bonsai-hero
BLADE;

        // Add props
        foreach ($data as $key => $value) {
            $template .= "\n        :{$key}=\"\${$dataVarName}['{$key}']\"";
        }

        $template .= "\n    />\n</div>\n";

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

        // If using defaults, skip prompts and use default values
        if ($useDefault) {
            $data = $this->getDefaultData($schema);
        } else {
            // Check for data in environment variables
            $data = [];
            foreach ($schema as $key => $field) {
                $envKey = "BONSAI_DATA_{$key}";
                $envValue = getenv($envKey);
                
                if ($envValue !== false) {
                    if ($field['type'] === 'array' || $field['type'] === 'object') {
                        $data[$key] = json_decode($envValue, true);
                    } else {
                        $data[$key] = $envValue;
                    }
                    continue;
                }
                
                // If no environment variable, use prompt
                $this->info("No pre-configured data found for {$key}, prompting...");
            }

            // If no data was found in environment variables, prompt for it
            if (empty($data)) {
                $data = $this->promptForData($schema);
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
                } else {
                    $data[$key] = [];
                    // Create default number of items
                    $count = $field['default'] ?? 3;
                    for ($i = 0; $i < $count; $i++) {
                        $item = [];
                        foreach ($field['schema'] as $subKey => $subField) {
                            $item[$subKey] = $subField['default'] ?? '';
                        }
                        $data[$key][] = $item;
                    }
                }
            } elseif ($field['type'] === 'object') {
                $data[$key] = [];
                foreach ($field['schema'] as $subKey => $subField) {
                    $data[$key][$subKey] = $subField['default'] ?? '';
                }
            }
        }
        return $data;
    }
}