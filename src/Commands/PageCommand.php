<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;

class PageCommand extends Command
{
    protected $signature = 'bonsai:page {title} {--layout=} {--page-template=}';
    protected $description = 'Create a new WordPress page with a custom template and layout';

    protected $files;
    protected const TEMPLATE_BASE_PATH = 'resources/views';

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $title = $this->argument('title');
        $layout = strtolower($this->option('layout') ?? 'default');
        $pageTemplate = $this->option('page-template');
        $slug = strtolower(str_replace(' ', '-', $title));

        // Use provided template name or create new one
        $templateName = $pageTemplate ?? $this->createTemplate($slug, $layout);
        
        // Create or update WordPress page
        $this->createOrUpdatePage($title, $slug, $templateName);
    }

    protected function createTemplate($slug, $layout)
    {
        // Standardize template path to be in root views directory
        $templateName = "template-{$slug}.blade.php";
        $templatePath = base_path(self::TEMPLATE_BASE_PATH . "/{$templateName}");
        
        // Ensure directory exists
        $this->files->ensureDirectoryExists(dirname($templatePath));

        if (!$this->files->exists($templatePath)) {
            $stubContent = $this->getTemplateStubContent($layout, $slug);
            $this->files->put($templatePath, $stubContent);
            $this->info("Template file created at: {$templatePath}");
        } else {
            $this->warn("Template file already exists at: {$templatePath}");
        }

        return $templateName;
    }

    protected function createOrUpdatePage($title, $slug, $templateName)
    {
        $pageId = DB::table('posts')
            ->where('post_type', 'page')
            ->where('post_name', $slug)
            ->value('ID');

        $pageData = [
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => [
                '_wp_page_template' => "template-{$slug}.blade.php"
            ],
        ];

        if ($pageId) {
            $pageData['ID'] = $pageId;
            wp_update_post($pageData);
            $this->info("Page '{$title}' updated with ID: {$pageId}");
        } else {
            $pageId = wp_insert_post($pageData);
            if (is_wp_error($pageId)) {
                $this->error("Failed to create page: " . $pageId->get_error_message());
                return;
            }
            $this->info("Page '{$title}' created with ID: {$pageId}");
        }

        // Set as homepage if specified in config
        if ($slug === 'cypress') {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $pageId);
            $this->info("Set '{$title}' as static homepage");
        }
    }

    protected function getTemplateStubContent($layout, $slug)
    {
        // If this is the cypress template, use the specific layout
        if ($slug === 'cypress') {
            return <<<BLADE
{{--
    Template Name: Cypress Template
--}}
@extends('bonsai.layouts.cypress')

@section('content')
    @include('bonsai.sections.home_hero')
    @include('bonsai.sections.features')
    @include('bonsai.sections.services_faq')
@endsection
BLADE;
        }

        // Default template for other pages
        return <<<BLADE
{{--
    Template Name: {$layout} Layout Template
--}}
@extends('bonsai.layouts.{$layout}')
BLADE;
    }
}