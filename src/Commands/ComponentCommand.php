<?php

namespace Bonsai\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ComponentCommand extends Command
{
    protected $signature = 'bonsai:component {name}';
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
        $templatePath = __DIR__ . "/../../templates/components/{$name}.blade.php";
        $componentPath = resource_path("views/bonsai/components/{$name}.blade.php");

        if (!$this->files->exists($templatePath)) {
            $this->error("Component {$name} does not have a template defined in Bonsai CLI.");
            return;
        }

        if ($this->files->exists($componentPath)) {
            $this->error("Component {$name} already exists at {$componentPath}");
            return;
        }

        $this->files->ensureDirectoryExists(resource_path('views/bonsai/components'));
        $this->files->copy($templatePath, $componentPath);

        $this->info("Component {$name} created at {$componentPath}");
    }
}
