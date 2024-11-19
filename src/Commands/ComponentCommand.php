<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ComponentCommand extends Command
{
    protected $signature = 'bonsai:component {name} {--default : Use default configuration without prompting}';
    protected $description = 'Create a Bonsai component in the project';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = strtolower($this->argument('name'));
        $useDefault = $this->option('default');
        
        $templatePath = __DIR__ . "/../../templates/components/{$name}.blade.php";
        $componentPath = resource_path("views/bonsai/components/{$name}.blade.php");
    
        if (!$this->files->exists($templatePath)) {
            $this->error("Component {$name} does not have a template defined in Bonsai CLI.");
            return;
        }
    
        // Ensure the target directory exists
        $this->files->ensureDirectoryExists(resource_path('views/bonsai/components'));
    
        if ($this->files->exists($componentPath)) {
            if ($useDefault) {
                $this->info("Component {$name} already exists, skipping.");
                return;
            }
            $this->error("Component {$name} already exists at {$componentPath}");
            return;
        }
    
        $this->files->copy($templatePath, $componentPath);
    
        if ($useDefault) {
            $this->line("Component {$name} created at {$componentPath}");
        } else {
            $this->info("Component {$name} created at {$componentPath}");
        }
    }
}