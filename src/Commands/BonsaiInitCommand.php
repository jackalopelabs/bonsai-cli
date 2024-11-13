<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class BonsaiInitCommand extends Command
{
    protected $signature = 'bonsai:init';
    protected $description = 'Initialize project by creating a Components page and setting up default templates';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        // Step 1: Create the "Components" Page
        $pageTitle = 'Components';
        $pageSlug = 'components';
        
        $pageExists = DB::table('posts')
            ->where('post_type', 'page')
            ->where('post_name', $pageSlug)
            ->exists();

        if (!$pageExists) {
            $pageId = wp_insert_post([
                'post_title'   => $pageTitle,
                'post_name'    => $pageSlug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => [
                    '_wp_page_template' => 'template-components.blade.php',
                ],
            ]);

            if (is_wp_error($pageId)) {
                $this->error("Failed to create the Components page: " . $pageId->get_error_message());
                return;
            }

            $this->info("Created Components page with ID: {$pageId}");
        } else {
            $this->info("Components page already exists.");
        }

        // Step 2: Create the Blade template file for Components
        $templatePath = resource_path("views/template-components.blade.php");

        if (!$this->files->exists($templatePath)) {
            $stubContent = $this->getTemplateStubContent($pageTitle);
            $this->files->put($templatePath, $stubContent);
            $this->info("Created Blade template: {$templatePath}");
        } else {
            $this->info("Blade template for Components already exists.");
        }

        $this->info("Bonsai init process completed successfully.");
    }

    protected function getTemplateStubContent($title)
    {
        return <<<BLADE
{{--
    Template Name: Components Template
--}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto py-10">
        <h1 class="text-3xl font-bold mb-8">{$title} Showcase</h1>
        <p class="mb-4">Explore various Blade UI Kit components below:</p>

        {{-- Button Components --}}
        <h2 class="text-2xl font-semibold mt-6">Buttons</h2>
        <div class="space-x-2 mt-2">
            <x-button>Default Button</x-button>
            <x-button.primary>Primary Button</x-button.primary>
            <x-button.secondary>Secondary Button</x-button.secondary>
        </div>

        {{-- Form Elements --}}
        <h2 class="text-2xl font-semibold mt-6">Form Elements</h2>
        <div class="mt-2">
            <x-form>
                <x-form.input name="name" label="Name" />
                <x-form.email name="email" label="Email" />
                <x-form.password name="password" label="Password" />
                <x-button.primary type="submit">Submit</x-button.primary>
            </x-form>
        </div>

        {{-- Modal Components --}}
        <h2 class="text-2xl font-semibold mt-6">Modal</h2>
        <x-modal>
            <x-slot name="trigger">
                <x-button.primary>Open Modal</x-button.primary>
            </x-slot>
            <x-slot name="title">Modal Title</x-slot>
            <p>This is the content of the modal.</p>
            <x-slot name="footer">
                <x-button.secondary>Close</x-button.secondary>
            </x-slot>
        </x-modal>

        {{-- Icon Components --}}
        <h2 class="text-2xl font-semibold mt-6">Icons</h2>
        <div class="space-x-4 mt-2">
            <x-icon name="home" class="w-6 h-6 text-blue-500" />
            <x-icon name="user" class="w-6 h-6 text-green-500" />
            <x-icon name="settings" class="w-6 h-6 text-red-500" />
        </div>
    </div>
@endsection
BLADE;
    }
}
