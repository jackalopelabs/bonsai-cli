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
        'resources/views/bonsai/components',
        'resources/views/bonsai/sections',
        'resources/views/bonsai/layouts',
    ];

    protected $templatePatterns = [
        'resources/views/template-*.blade.php',
        'resources/views/templates/template-*.blade.php'
    ];

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will remove all Bonsai-generated content. Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->cleanupFiles();
        $this->cleanupTemplates();
        $this->cleanupWordPressContent();
        $this->cleanupMenus();
        $this->resetTemplateRegistry();
        
        $this->info('Cleanup completed successfully!');
    }

    protected function cleanupFiles()
    {
        $this->info('Cleaning up generated files...');
        
        // First clean up individual files in bonsai directories
        $this->cleanupBonsaiFiles();
        
        // Then remove empty directories
        foreach ($this->generatedPaths as $path) {
            $fullPath = base_path($path);
            
            if (File::exists($fullPath)) {
                try {
                    if ($this->isDirectoryEmpty($fullPath)) {
                        File::deleteDirectory($fullPath);
                        $this->line("- Removed empty directory: {$path}");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to remove {$path}: " . $e->getMessage());
                }
            }
        }

        // Try to remove parent bonsai directory if empty
        $bonsaiDir = base_path('resources/views/bonsai');
        if (File::exists($bonsaiDir) && $this->isDirectoryEmpty($bonsaiDir)) {
            try {
                File::deleteDirectory($bonsaiDir);
                $this->line("- Removed empty bonsai directory");
            } catch (\Exception $e) {
                $this->error("Failed to remove bonsai directory: " . $e->getMessage());
            }
        }
    }

    protected function cleanupBonsaiFiles()
    {
        // Clean up sections
        $sectionsPath = base_path('resources/views/bonsai/sections');
        if (File::exists($sectionsPath)) {
            foreach (File::glob("{$sectionsPath}/*.blade.php") as $file) {
                File::delete($file);
                $this->line("- Removed section: " . basename($file));
            }
        }

        // Clean up layouts
        $layoutsPath = base_path('resources/views/bonsai/layouts');
        if (File::exists($layoutsPath)) {
            foreach (File::glob("{$layoutsPath}/*.blade.php") as $file) {
                File::delete($file);
                $this->line("- Removed layout: " . basename($file));
            }
        }
    }

    protected function cleanupTemplates()
    {
        $this->info('Cleaning up template files...');
        
        foreach ($this->templatePatterns as $pattern) {
            $files = glob(base_path($pattern));
            foreach ($files as $file) {
                try {
                    if (File::exists($file)) {
                        File::delete($file);
                        $this->line("- Removed template: " . basename($file));
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to remove template {$file}: " . $e->getMessage());
                }
            }
        }

        // Cleanup empty templates directory if it exists
        $templatesDir = base_path('resources/views/templates');
        if (File::exists($templatesDir) && count(File::files($templatesDir)) === 0) {
            try {
                File::deleteDirectory($templatesDir);
                $this->line("- Removed empty templates directory");
            } catch (\Exception $e) {
                $this->error("Failed to remove templates directory: " . $e->getMessage());
            }
        }
    }

    protected function cleanupWordPressContent()
    {
        $this->info('Cleaning up WordPress pages...');

        // Query for pages with any Bonsai-related template
        $args = [
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_wp_page_template',
                    'value' => 'bonsai-%',
                    'compare' => 'LIKE'
                ],
                [
                    'key' => '_wp_page_template',
                    'value' => 'template-%',
                    'compare' => 'LIKE'
                ],
                [
                    'key' => '_wp_page_template',
                    'value' => 'templates/template-%',
                    'compare' => 'LIKE'
                ]
            ]
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();
                $template = get_post_meta($postId, '_wp_page_template', true);
                
                try {
                    wp_delete_post($postId, true);
                    $this->line("- Removed page: " . get_the_title() . " (template: {$template})");
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
                            && ($template = get_post_meta($item->object_id, '_wp_page_template', true))
                        ) {
                            // Check for any Bonsai-related template pattern
                            if (strpos($template, 'bonsai-') === 0 
                                || strpos($template, 'template-') === 0 
                                || strpos($template, 'templates/template-') === 0
                            ) {
                                wp_delete_post($item->ID, true);
                                $this->line("- Removed menu item: {$item->title} (template: {$template})");
                            }
                        }
                    }
                }
            }
        }

        // Clear menu and post caches
        wp_cache_delete('last_changed', 'posts');
        wp_cache_delete('last_changed', 'nav_menu_items');
    }

    protected function resetTemplateRegistry()
    {
        $this->info('Resetting template registry...');
        
        // Clear the page templates option
        delete_option('page_templates');
        
        // Clear related caches
        wp_cache_delete('page_templates');
        
        // Reset theme mods related to templates
        $theme = get_option('stylesheet');
        $mods = get_option("theme_mods_{$theme}");
        if (is_array($mods) && isset($mods['page_templates'])) {
            unset($mods['page_templates']);
            update_option("theme_mods_{$theme}", $mods);
        }
        
        $this->line("- Template registry reset successfully");
    }

    protected function isDirectoryEmpty($dir)
    {
        return count(array_diff(scandir($dir), ['.', '..'])) === 0;
    }
}