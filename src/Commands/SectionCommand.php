<?php

namespace JackalopeLabs\BonsaiCli\Commands;

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
    
        // New search order for template config
        $possiblePaths = [
            base_path("config/bonsai/templates/{$template}.yml"),    // Local new templates directory
            base_path("config/bonsai/{$template}.yml"),             // Legacy project config
            base_path("config/templates/{$template}.yml"),          // Legacy templates
            __DIR__ . "/../../config/templates/{$template}.yml"     // Package default templates
        ];
    
        $configPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $configPath = $path;
                break;
            }
        }
    
        if (!$configPath) {
            // No config found, fallback to basic schema
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
    
        // If we reach here, no matching section was found in config
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
        
        // Add default values for pricing section
        if ($componentName === 'pricing' && !isset($data['subtitle'])) {
            $data = array_merge([
                'title' => 'Choose Your Plan',
                'subtitle' => 'Limited-time pricing available now',
                'description' => 'Select the plan that best suits your needs.',
                'pricingBoxes' => []
            ], $data);
        }

        $template = <<<BLADE
@props([
    'class' => ''
])

@php
\${$dataVarName} = [

BLADE;

        // Handle each data field
        foreach ($data as $key => $value) {
            if ($key === 'iconMappings') {
                // Special handling for icon mappings
                $template .= "    '{$key}' => " . $this->arrayToPhpString($value, 1) . ",\n";
            } elseif (is_array($value)) {
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
BLADE;

        // Special handling for pricing section
        if ($componentName === 'pricing') {
            $template .= <<<BLADE
    
    <section class="py-24" id="plans">
        <div class="py-12">
            <div class="mx-auto px-4 text-center">
                <div class="inline-flex items-center gap-2 rounded-md bg-white text-sm px-3 py-1 text-center mb-4">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-gray-400">{{ \${$dataVarName}['subtitle'] }}</span>
                </div>
                <h2 class="text-5xl font-bold text-gray-900 mb-4 pt-4">{{ \${$dataVarName}['title'] }}</h2>
                <p class="text-gray-500 mb-8">{{ \${$dataVarName}['description'] }}</p>
            </div>
        </div>

        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-center items-start space-y-8 md:space-y-0 md:space-x-8">
                @foreach (\${$dataVarName}['pricingBoxes'] as \$box)
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
            // Default component rendering with new namespace
            $template .= <<<BLADE
    <x-bonsai::{$componentName}
BLADE;
            // Add props
            foreach ($data as $key => $value) {
                $template .= "\n        :{$key}=\"\${$dataVarName}['{$key}']\"";
            }
            $template .= "\n    />";
        }

        $template .= "\n</div>\n";

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

        // Get data from environment variables first
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
                // If no environment variable and using defaults, use schema default
                if ($useDefault) {
                    $data[$key] = $field['default'];
                    $this->info("Using default value for {$key}");
                } else {
                    // If not using defaults and no env var, prompt for input
                    if ($field['type'] === 'array') {
                        $data[$key] = $field['default']; // For arrays, use default if no env var
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
    <x-bonsai::header
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