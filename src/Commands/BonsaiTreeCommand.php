<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Jackalopelabs\BonsaiCli\Generators\TreeGenerator;
use Jackalopelabs\BonsaiCli\Storage\BonsaiTreeStorage;

class BonsaiTreeCommand extends Command
{
    protected $signature = 'bonsai:tree
                          {action=generate : Action to perform (generate, list, age, assign)}
                          {--config= : Path to config file}
                          {--season= : Season for tree generation}
                          {--age= : Age category for the tree}
                          {--style= : Bonsai style (formal, informal, slanting, cascade)}
                          {--seed= : Random seed for generation}
                          {--force : Force regeneration of existing tree}';

    protected $description = 'Generate and manage ASCII art bonsai trees';

    protected $generator;
    protected $storage;

    public function __construct(TreeGenerator $generator, BonsaiTreeStorage $storage)
    {
        parent::__construct();
        $this->generator = $generator;
        $this->storage = $storage;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $config = $this->option('config');

        if ($this->getVerbosity() > Command::VERBOSITY_NORMAL) {
            $this->generator->enableDebug();
        }

        switch ($action) {
            case 'generate':
                return $this->generateTree();
            case 'list':
                return $this->listTrees();
            case 'age':
                return $this->ageTree();
            case 'assign':
                return $this->assignTree();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function generateTree()
    {
        $config = $this->option('config');
        if (!$config) {
            $this->error('Config path is required for tree generation');
            return 1;
        }

        $options = [
            'season' => $this->option('season'),
            'age' => $this->option('age'),
            'style' => $this->option('style'),
            'seed' => $this->option('seed'),
        ];

        try {
            $tree = $this->generator->generate($options);
            
            if ($this->storage->exists($config) && !$this->option('force')) {
                if (!$this->confirm('Tree already exists for this config. Overwrite?')) {
                    return 1;
                }
            }

            $this->storage->store($config, $tree);
            $this->info('Tree generated and stored successfully!');
            $this->line($tree->render());
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate tree: " . $e->getMessage());
            return 1;
        }
    }

    protected function listTrees()
    {
        $trees = $this->storage->all();
        
        if (empty($trees)) {
            $this->info('No trees found.');
            return 0;
        }

        $headers = ['Config', 'Age', 'Style', 'Created', 'Last Modified'];
        $rows = [];

        foreach ($trees as $config => $tree) {
            $rows[] = [
                $config,
                $tree->age,
                $tree->style,
                $tree->created_at->diffForHumans(),
                $tree->updated_at->diffForHumans(),
            ];
        }

        $this->table($headers, $rows);
        return 0;
    }

    protected function ageTree()
    {
        $config = $this->option('config');
        if (!$config) {
            $this->error('Config path is required for aging trees');
            return 1;
        }

        try {
            $tree = $this->storage->get($config);
            $aged = $this->generator->age($tree);
            $this->storage->store($config, $aged);
            
            $this->info('Tree aged successfully!');
            $this->line($aged->render());
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to age tree: " . $e->getMessage());
            return 1;
        }
    }

    protected function assignTree()
    {
        $config = $this->option('config');
        if (!$config) {
            $this->error('Config path is required for assigning trees');
            return 1;
        }

        $style = $this->option('style') ?? 'formal';
        
        try {
            $tree = $this->generator->generate(['style' => $style]);
            
            if ($this->storage->exists($config) && !$this->option('force')) {
                if (!$this->confirm('Config already has a tree. Replace it?')) {
                    return 1;
                }
            }

            $this->storage->store($config, $tree);
            $this->info('Tree assigned successfully!');
            $this->line($tree->render());
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to assign tree: " . $e->getMessage());
            return 1;
        }
    }
} 