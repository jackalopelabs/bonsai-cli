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
                    'default' => 'A modern web testing tool for modern applications'
                ],
                'imagePaths' => [
                    'type' => 'array',
                    'prompt' => 'Enter image paths (comma-separated)',
                    'default' => ['https://placehold.co/600x400/png']
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
            'header' => [
                'siteName' => [
                    'type' => 'string',
                    'prompt' => 'Enter site name',
                    'default' => 'Cypress'
                ],
                'iconComponent' => [
                    'type' => 'string',
                    'prompt' => 'Enter icon component name',
                    'default' => 'icon-bonsai'
                ],
                'navLinks' => [
                    'type' => 'array',
                    'prompt' => 'How many navigation links?',
                    'default' => [
                        ['url' => '#features', 'label' => 'Features'],
                        ['url' => '#pricing', 'label' => 'Pricing'],
                        ['url' => '#faq', 'label' => 'FAQ'],
                    ]
                ],
                'primaryLink' => [
                    'type' => 'string',
                    'prompt' => 'Enter primary link URL',
                    'default' => '#signup'
                ],
                'containerClasses' => [
                    'type' => 'string',
                    'prompt' => 'Enter container classes',
                    'default' => 'max-w-5xl mx-auto'
                ],
                'containerInnerClasses' => [
                    'type' => 'string',
                    'prompt' => 'Enter inner container classes',
                    'default' => 'px-6'
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
    <x-bonsai-{$componentName}
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
                } elseif (isset($field['schema'])) {
                    // Only process schema if it exists
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
                    // Handle arrays without schema
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
        // Get data from environment variables or use defaults
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
    <x-bonsai-header
        :siteName="\$headerData['siteName']"
        :iconComponent="\$headerData['iconComponent']"
        :navLinks="\$headerData['navLinks']"
        :primaryLink="\$headerData['primaryLink']"
        :containerClasses="\$headerData['containerClasses']"
        :containerInnerClasses="\$headerData['containerInnerClasses']"
    />
</div>
BLADE;

        return $content;
    }
}