<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WP_Query;

class CleanupCommand extends Command
{
    protected $signature = 'bonsai:cleanup {--force : Force cleanup without confirmation}';
    protected $description = 'Clean up all Bonsai-generated content and start fresh';

    protected $generatedPaths = [
        'resources/views/components/bonsai',
        'resources/views/sections/bonsai',
        'resources/views/layouts/bonsai',
        'resources/views/pages/bonsai',
    ];

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will remove all Bonsai-generated content. Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->cleanupFiles();
        $this->cleanupWordPressContent();
        $this->cleanupMenus();
        
        $this->info('Cleanup completed successfully!');
    }

    protected function cleanupFiles()
    {
        $this->info('Cleaning up generated files...');
        
        foreach ($this->generatedPaths as $path) {
            $fullPath = base_path($path);
            
            if (File::exists($fullPath)) {
                try {
                    File::deleteDirectory($fullPath);
                    $this->line("- Removed directory: {$path}");
                } catch (\Exception $e) {
                    $this->error("Failed to remove {$path}: " . $e->getMessage());
                }
            }
        }
    }

    protected function cleanupWordPressContent()
    {
        $this->info('Cleaning up WordPress pages...');

        // Query for pages with Bonsai template
        $args = [
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_wp_page_template',
                    'value' => 'bonsai-%',
                    'compare' => 'LIKE'
                ]
            ]
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();
                
                try {
                    wp_delete_post($postId, true);
                    $this->line("- Removed page: " . get_the_title());
                } catch (\Exception $e) {
                    $this->error("Failed to remove page {$postId}: " . $e->getMessage());
                }
            }
        }

        wp_reset_postdata();
    }

    protected function cleanupMenus()
    {
        $this->info('Cleaning up menu references...');

        // Get all menu locations
        $locations = get_nav_menu_locations();

        foreach ($locations as $location => $menu_id) {
            if ($menu_id) {
                $menu_items = wp_get_nav_menu_items($menu_id);
                
                if ($menu_items) {
                    foreach ($menu_items as $item) {
                        // Check if menu item points to a Bonsai page
                        if ($item->type === 'post_type' 
                            && $item->object === 'page' 
                            && get_post_meta($item->object_id, '_wp_page_template', true) 
                            && strpos(get_post_meta($item->object_id, '_wp_page_template', true), 'bonsai-') === 0
                        ) {
                            wp_delete_post($item->ID, true);
                            $this->line("- Removed menu item: {$item->title}");
                        }
                    }
                }
            }
        }

        // Clear menu cache
        wp_cache_delete('last_changed', 'posts');
    }
}