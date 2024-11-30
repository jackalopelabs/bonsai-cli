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
        'resources/views/bonsai',
        'config/bonsai',
        'app/View/Components/Bonsai',
        'resources/views/template-components.blade.php',
        'scripts/bonsai.sh',
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
        $this->resetTemplateRegistry();
        
        $this->info('Cleanup completed successfully!');
    }

    protected function cleanupFiles()
    {
        $this->info('Cleaning up generated files...');
        
        foreach ($this->generatedPaths as $path) {
            $fullPath = base_path($path);
            
            if (File::exists($fullPath)) {
                try {
                    if (is_dir($fullPath)) {
                        File::deleteDirectory($fullPath);
                    } else {
                        File::delete($fullPath);
                    }
                    $this->line("- Removed: {$path}");
                } catch (\Exception $e) {
                    $this->error("Failed to remove {$path}: " . $e->getMessage());
                }
            }
        }
    }

    protected function cleanupWordPressContent()
    {
        $this->info('Cleaning up WordPress pages...');

        // Query for Bonsai-generated pages
        $args = [
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                // Check for Bonsai generated flag
                [
                    'key' => '_bonsai_generated',
                    'value' => 'true',
                ],
                // Check for components template
                [
                    'key' => '_wp_page_template',
                    'value' => 'template-components.blade.php',
                    'compare' => '=',
                ],
                // Check for bonsai template pattern
                [
                    'key' => '_wp_page_template',
                    'value' => 'bonsai/templates/template-',
                    'compare' => 'LIKE',
                ],
                // Check for views/bonsai template pattern
                [
                    'key' => '_wp_page_template',
                    'value' => 'views/bonsai/templates/template-',
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();
                $template = get_post_meta($postId, '_wp_page_template', true);
                $title = get_the_title();
                
                try {
                    // If this was set as homepage, reset the option
                    if (get_option('page_on_front') == $postId) {
                        update_option('show_on_front', 'posts');
                        update_option('page_on_front', 0);
                        $this->line("- Reset homepage setting");
                    }

                    wp_delete_post($postId, true);
                    $this->line("- Removed page: {$title} (template: {$template})");
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

        $locations = get_nav_menu_locations();

        foreach ($locations as $location => $menu_id) {
            if ($menu_id) {
                $menu_items = wp_get_nav_menu_items($menu_id);
                
                if ($menu_items) {
                    foreach ($menu_items as $item) {
                        if ($item->type === 'post_type' 
                            && $item->object === 'page' 
                            && ($template = get_post_meta($item->object_id, '_wp_page_template', true))
                        ) {
                            if (strpos($template, 'bonsai/') === 0 
                                || strpos($template, 'template-') === 0
                            ) {
                                wp_delete_post($item->ID, true);
                                $this->line("- Removed menu item: {$item->title}");
                            }
                        }
                    }
                }
            }
        }

        wp_cache_delete('last_changed', 'posts');
        wp_cache_delete('last_changed', 'nav_menu_items');
    }

    protected function resetTemplateRegistry()
    {
        $this->info('Resetting template registry...');
        
        delete_option('page_templates');
        wp_cache_delete('page_templates');
        
        $theme = get_option('stylesheet');
        $mods = get_option("theme_mods_{$theme}");
        if (is_array($mods) && isset($mods['page_templates'])) {
            unset($mods['page_templates']);
            update_option("theme_mods_{$theme}", $mods);
        }
        
        $this->line("- Template registry reset successfully");
    }
}