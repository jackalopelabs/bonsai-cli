<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class CleanupCommand extends Command
{
    protected $signature = 'bonsai:cleanup {--force}';
    protected $description = 'Clean up all Bonsai-generated content and files';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will remove all Bonsai-generated content. Are you sure?')) {
                return;
            }
        }

        $this->info('Starting Bonsai cleanup...');

        // Clean up WordPress pages
        $this->cleanupPages();

        // Clean up directories
        $this->cleanupDirectories();

        // Clean up menus
        $this->cleanupMenus();

        $this->info('🧹 Bonsai cleanup completed successfully!');
    }

    protected function cleanupPages()
    {
        $this->info('Cleaning up WordPress pages...');
        
        // Find pages using Bonsai templates
        $pages = DB::table('posts')
            ->where('post_type', 'page')
            ->get();

        foreach ($pages as $page) {
            $template = get_post_meta($page->ID, '_wp_page_template', true);
            if (str_starts_with($template, 'template-')) {
                wp_delete_post($page->ID, true);
                $this->info("Deleted page: {$page->post_title}");
            }
        }
    }

    protected function cleanupDirectories()
    {
        $this->info('Cleaning up Bonsai directories...');

        $directories = [
            resource_path('views/bonsai'),
            resource_path('views/templates'),
            base_path('config/bonsai')
        ];

        foreach ($directories as $directory) {
            if ($this->files->exists($directory)) {
                $this->files->deleteDirectory($directory);
                $this->info("Removed directory: {$directory}");
            }
        }
    }

    protected function cleanupMenus()
    {
        $this->info('Cleaning up menus...');
        
        // Get all menu locations
        $locations = get_nav_menu_locations();
        
        // Remove all menu assignments
        set_theme_mod('nav_menu_locations', []);
        
        // Delete any auto-generated menus
        $menus = wp_get_nav_menus();
        if ($menus) {
            foreach ($menus as $menu) {
                if (str_contains(strtolower($menu->name), 'bonsai')) {
                    wp_delete_nav_menu($menu->term_id);
                    $this->info("Deleted menu: {$menu->name}");
                }
            }
        }

        // Clear menu caches
        wp_cache_delete('last_changed', 'terms');
    }
}