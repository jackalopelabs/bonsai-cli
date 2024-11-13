<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ComponentCommand extends Command
{
    protected $signature = 'bonsai:component {name}';
    protected $description = 'Create a new component from the template stubs';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = strtolower($this->argument('name'));

        // Define the paths
        $stubPath = base_path("bonsai-cli/templates/components/{$name}.blade.php");
        $componentPath = resource_path("views/components/{$name}.blade.php");

        // Check if the component template exists
        if (!$this->files->exists($stubPath)) {
            $this->error("Component {$name} does not have a template defined in Bonsai CLI.");
            return;
        }

        // Ensure the components directory exists
        $this->files->ensureDirectoryExists(resource_path('views/components'));

        // Copy the template to the components directory
        $stubContent = $this->files->get($stubPath);
        $this->files->put($componentPath, $stubContent);

        $this->info("Component {$name} created at {$componentPath}");
    }
}
