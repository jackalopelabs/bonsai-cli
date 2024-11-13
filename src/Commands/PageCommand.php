<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;

class PageCommand extends Command
{
    protected $signature = 'bonsai:page {title*} {--layout=}';
    protected $description = 'Create a new WordPress page with a custom template and layout';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $titleArray = $this->argument('title');
        $title = implode(' ', $titleArray);
        $layout = strtolower($this->option('layout') ?? 'default');
        $slug = strtolower(str_replace(' ', '-', $title));

        // Ensure the template directory exists
        $templateDirectory = resource_path("views/templates");
        $this->files->ensureDirectoryExists($templateDirectory);

        // Step 1: Create the Blade template
        $templatePath = "{$templateDirectory}/template-{$slug}.blade.php";
        if (!$this->files->exists($templatePath)) {
            $stubContent = $this->getTemplateStubContent($layout);
            $this->files->put($templatePath, $stubContent);
            $this->info("Template file created at: {$templatePath}");
        } else {
            $this->warn("Template file already exists at: {$templatePath}");
        }

        // Step 2: Register the template with WordPress
        add_filter('theme_page_templates', function ($templates) use ($slug) {
            $templates["templates/template-{$slug}.blade.php"] = ucfirst(str_replace('-', ' ', $slug)) . ' Template';
            return $templates;
        });

        // Step 3: Create the WordPress page
        $pageId = DB::table('posts')
            ->where('post_type', 'page')
            ->where('post_name', $slug)
            ->value('ID');

        if ($pageId) {
            $this->warn("Page '{$title}' already exists with ID: {$pageId}");
        } else {
            $pageId = wp_insert_post([
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => [
                    '_wp_page_template' => "templates/template-{$slug}.blade.php"
                ],
            ]);

            if (is_wp_error($pageId)) {
                $this->error("Failed to create page: " . $pageId->get_error_message());
                return;
            }

            $this->info("Page '{$title}' created with ID: {$pageId}");
        }
    }

    protected function getTemplateStubContent($layout)
    {
        return <<<BLADE
{{--
    Template Name: Custom Template for {$layout} layout
--}}
@extends('bonsai.layouts.{$layout}')
BLADE;
    }
}
