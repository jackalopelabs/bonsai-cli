<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ScionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('scion')
            ->setDescription('Reverse-engineer a Roots template into a Bonsai CLI YAML configuration')
            ->addArgument('templateName', InputArgument::REQUIRED, 'The name of the template to generate')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'The source template file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Retrieve the source file path
        $source = $input->getOption('source');
        if (!$source || !file_exists($source)) {
            $output->writeln("<error>Please provide a valid source file path using the --source option.</error>");
            return Command::FAILURE;
        }

        // Get the template name
        $templateName = $input->getArgument('templateName');
        $output->writeln("<info>Starting extraction for template: {$templateName}</info>");

        // Read the template file
        $content = file_get_contents($source);

        // Extract all Blade @include directives
        preg_match_all("/@include\(['\"]([^'\"]+)['\"]\)/", $content, $matches);
        $includes = $matches[1] ?? [];

        if (empty($includes)) {
            $output->writeln("<error>No include directives found in the template.</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Found the following includes:</info>");
        foreach ($includes as $include) {
            $output->writeln(" - " . $include);
        }

        // For each include, prompt for section key and component mapping
        $helper = $this->getHelper('question');
        $sections = [];
        
        foreach ($includes as $include) {
            // Extract the last part of the include path as the default key
            $parts = explode('.', $include);
            $defaultKey = end($parts);
            
            $sectionKeyQuestion = new Question("Enter section key for include '{$include}' [{$defaultKey}]: ", $defaultKey);
            $sectionKey = $helper->ask($input, $output, $sectionKeyQuestion);
            
            // Determine default component based on section name
            $defaultComponent = $this->guessComponentType($sectionKey);
            $componentQuestion = new Question("Enter component for section '{$sectionKey}' [{$defaultComponent}]: ", $defaultComponent);
            $component = $helper->ask($input, $output, $componentQuestion);

            // Get component-specific data
            $data = $this->getComponentData($helper, $input, $output, $component, $sectionKey);

            $sections[$sectionKey] = [
                'component' => $component,
                'data'      => $data,
            ];
        }

        // Ask the user for layout order
        $defaultOrder = implode(',', array_keys($sections));
        $orderQuestion = new Question("Enter comma-separated section keys for layout order [{$defaultOrder}]: ", $defaultOrder);
        $orderInput = $helper->ask($input, $output, $orderQuestion);
        $layoutSections = array_map('trim', explode(',', $orderInput));

        // Build the configuration array
        $config = [
            'sections' => $sections,
            'layout'   => [
                'sections' => $layoutSections,
            ],
        ];

        // Dump the configuration array to YAML
        $yamlContent = Yaml::dump($config, 4, 2);

        // Save the YAML file to config/bonsai/templates/{templateName}.yml
        $targetPath = __DIR__ . '/../../config/bonsai/templates/' . $templateName . '.yml';

        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0755, true);
        }

        file_put_contents($targetPath, $yamlContent);

        $output->writeln("<info>Configuration saved to: {$targetPath}</info>");
        $output->writeln("<info>Now run 'wp acorn bonsai:generate {$templateName}' in your Roots project to generate the landing page.</info>");

        return Command::SUCCESS;
    }

    private function guessComponentType(string $sectionKey): string
    {
        if (strpos($sectionKey, 'hero') !== false) {
            return 'hero';
        }
        if (strpos($sectionKey, 'card') !== false) {
            return 'card';
        }
        if (strpos($sectionKey, 'widget') !== false) {
            return 'widget';
        }
        if (strpos($sectionKey, 'pricing') !== false) {
            return 'pricing';
        }
        return 'hero'; // default fallback
    }

    private function getComponentData($helper, $input, $output, string $component, string $sectionKey): array
    {
        $data = [];
        
        switch ($component) {
            case 'hero':
                $titleQuestion = new Question("Enter title for hero section '{$sectionKey}' [Welcome to Cypress]: ", "Welcome to Cypress");
                $subtitleQuestion = new Question("Enter subtitle for hero section '{$sectionKey}' [A Modern SaaS Landing Page]: ", "A Modern SaaS Landing Page");
                
                $data['title'] = $helper->ask($input, $output, $titleQuestion);
                $data['subtitle'] = $helper->ask($input, $output, $subtitleQuestion);
                break;

            case 'card':
                $titleQuestion = new Question("Enter title for card section '{$sectionKey}' [Our Services]: ", "Our Services");
                $subtitleQuestion = new Question("Enter subtitle for card section '{$sectionKey}' [What we offer]: ", "What we offer");
                
                $data['title'] = $helper->ask($input, $output, $titleQuestion);
                $data['subtitle'] = $helper->ask($input, $output, $subtitleQuestion);
                break;

            case 'widget':
                $titleQuestion = new Question("Enter title for widget section '{$sectionKey}' [Features]: ", "Features");
                $subtitleQuestion = new Question("Enter subtitle for widget section '{$sectionKey}' [What makes us different]: ", "What makes us different");
                
                $data['title'] = $helper->ask($input, $output, $titleQuestion);
                $data['subtitle'] = $helper->ask($input, $output, $subtitleQuestion);
                break;

            case 'pricing':
                $titleQuestion = new Question("Enter title for pricing section '{$sectionKey}' [Pricing Plans]: ", "Pricing Plans");
                $subtitleQuestion = new Question("Enter subtitle for pricing section '{$sectionKey}' [Choose the plan that's right for you]: ", "Choose the plan that's right for you");
                
                $data['title'] = $helper->ask($input, $output, $titleQuestion);
                $data['subtitle'] = $helper->ask($input, $output, $subtitleQuestion);
                break;
        }

        return $data;
    }
} 