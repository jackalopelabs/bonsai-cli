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
        'resources/js/pixel-matrix.js',
        'resources/views/bonsai/components',
        'resources/views/bonsai/sections',
        'resources/views/bonsai/layouts',
        'resources/views/templates',
        'resources/images/bonsai_hero_01.webp',
        'resources/images/bonsai_hero_03.webp',
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
        $this->cleanupTailwindConfig();
        $this->cleanupAppCss();
        $this->cleanupAppJs();
        $this->cleanupBonsaiImages();
        
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

    protected function cleanupTailwindConfig()
    {
        $this->info('Cleaning up Tailwind configuration...');
        
        // Check for both .ts and .js versions of the config
        $configPaths = [
            base_path('tailwind.config.ts'),
            base_path('tailwind.config.js')
        ];
        
        $configPath = null;
        foreach ($configPaths as $path) {
            if (File::exists($path)) {
                $configPath = $path;
                break;
            }
        }
        
        if (!$configPath) {
            $this->warn('No tailwind.config.ts or tailwind.config.js found');
            return;
        }

        try {
            $content = File::get($configPath);
            $modified = false;

            // Remove the bonsaiConfig import statement
            if (preg_match("/import\s+bonsaiConfig\s+from\s+['\"]\.\\/bonsai\.config['\"]\s*;?\n?/", $content)) {
                $content = preg_replace("/import\s+bonsaiConfig\s+from\s+['\"]\.\\/bonsai\.config['\"]\s*;?\n?/", '', $content);
                $modified = true;
            }

            // Remove the ...bonsaiConfig.colors spread without affecting the closing brace
            if (preg_match("/,?\s*\.\.\.bonsaiConfig\.colors(?=\s*[,}])/", $content)) {
                $content = preg_replace("/,?\s*\.\.\.bonsaiConfig\.colors(?=\s*[,}])/", "", $content);
                $modified = true;
            }

            // Remove or replace darkMode: 'class' configuration
            if (preg_match("/darkMode:\s*['\"](class|media)['\"],?(\r?\n)?/", $content)) {
                $content = preg_replace("/darkMode:\s*['\"](class|media)['\"],?(\r?\n)?/", "", $content);
                $modified = true;
            }
            
            // Remove midnight color if it was added by Bonsai
            if (preg_match("/midnight:\s*{[^}]*950:\s*['\"]#060614['\"][^}]*},?/s", $content)) {
                $content = preg_replace("/,?\s*midnight:\s*{[^}]*950:\s*['\"]#060614['\"][^}]*},?/s", "", $content);
                $modified = true;
            }

            // Clean up any potential double braces
            $content = preg_replace('/}{2,}/', '}', $content);

            // Clean up any potential double commas
            $content = preg_replace('/,(\s*,)+/', ',', $content);
            
            // Clean up any trailing commas before closing braces
            $content = preg_replace('/,(\s*})/', '$1', $content);

            // Fix theme indentation and ensure proper line break
            $content = preg_replace('/\],\s*theme:\s*{/', "],\n  theme: {", $content);

            // Clean up multiple empty lines
            $content = preg_replace("/\n{3,}/", "\n\n", $content);

            if ($modified) {
                File::put($configPath, $content);
                $this->line("- Cleaned up Tailwind configuration in " . basename($configPath));
            }
        } catch (\Exception $e) {
            $this->error("Failed to update " . basename($configPath) . ": " . $e->getMessage());
        }
    }

    protected function cleanupAppCss()
    {
        $this->info('Cleaning up app.css...');
        
        $appCssPath = base_path('resources/css/app.css');
        if (File::exists($appCssPath)) {
            try {
                $appCss = File::get($appCssPath);
                $modified = false;
                
                // Detect if this is a Sage 11 style app.css
                $isSage11Format = preg_match('/@import\s+["\']tailwindcss["\']/', $appCss);
                
                // Remove @theme block
                if (preg_match('/@theme\s*{[^}]*}/', $appCss)) {
                    $appCss = preg_replace('/@theme\s*{[^}]*}\s*\n?/', '', $appCss);
                    $modified = true;
                }
                
                // Remove the entire @layer base block
                if (preg_match('/@layer\s+base\s*{.*?}$/ms', $appCss)) {
                    $appCss = preg_replace('/@layer\s+base\s*{.*?}$/ms', '', $appCss);
                    $modified = true;
                }
                
                // For Sage 11, we need to be more careful to preserve the imports
                if ($isSage11Format) {
                    // Keep the imports but remove our custom additions
                    $importLines = [];
                    if (preg_match('/@import\s+["\']tailwindcss["\']\s+theme\(static\);/', $appCss, $matches)) {
                        $importLines[] = $matches[0];
                    }
                    if (preg_match('/@source\s+["\']\.\.\\/views\\/["\']\s*;/', $appCss, $matches)) {
                        $importLines[] = $matches[0];
                    }
                    if (preg_match('/@source\s+["\']\.\.\\/\.\.\\/app\\/["\']\s*;/', $appCss, $matches)) {
                        $importLines[] = $matches[0];
                    }
                    
                    // If we found the imports, replace the entire file with just those
                    if (!empty($importLines)) {
                        $newAppCss = implode("\n", $importLines);
                        if ($appCss !== $newAppCss) {
                            $appCss = $newAppCss;
                            $modified = true;
                        }
                    }
                } else {
                    // For Radicle, use the original pattern
                    $pattern = "/@theme\s*{[^}]*}.*?@layer\s+base\s*{.*?a:hover\s*{.*?}\s*}/s";
                    $cleanedCss = preg_replace($pattern, '', $appCss);
                    
                    if ($cleanedCss !== $appCss) {
                        $appCss = $cleanedCss;
                        $modified = true;
                    }
                }
                
                // Clean up multiple empty lines
                $appCss = preg_replace("/\n{3,}/", "\n\n", $appCss);
                
                if ($modified) {
                    File::put($appCssPath, $appCss);
                    $this->line("- Cleaned up Bonsai CSS from app.css");
                }
            } catch (\Exception $e) {
                $this->error("Failed to update app.css: " . $e->getMessage());
            }
        }
    }

    protected function cleanupAppJs()
    {
        $this->info('Cleaning up app.js...');
        
        $appJsPath = base_path('resources/js/app.js');
        
        if (File::exists($appJsPath)) {
            try {
                $content = File::get($appJsPath);
                $modified = false;
                
                // Detect if this is a Sage 11 style app.js
                $isSage11Format = preg_match('/import\.meta\.glob\(\s*\[\s*[\'"]\.\.\/images\/\*\*[\'"]/', $content);
                
                // Remove the PixelMatrix import
                if (str_contains($content, "import PixelMatrix from './pixel-matrix'")) {
                    $content = preg_replace("/import\s+PixelMatrix\s+from\s+['\"]\.\\/pixel-matrix['\"];?\n?/", '', $content);
                    $modified = true;
                }
                
                // Remove the PixelMatrix initialization code
                if (str_contains($content, "// Initialize PixelMatrix on pricing boxes")) {
                    $pattern = "/\/\/\s*Initialize\s+PixelMatrix\s+on\s+pricing\s+boxes\s*document\.addEventListener\(['\"]DOMContentLoaded['\"]\s*,\s*\(\)\s*=>\s*{\s*const\s+pricingBoxes\s*=\s*document\.querySelectorAll\(['\"]\.pricing-box['\"]\)\s*pricingBoxes\.forEach\(box\s*=>\s*new\s+PixelMatrix\(box\)\)\s*}\);?\n?/s";
                    $content = preg_replace($pattern, '', $content);
                    $modified = true;
                }
                
                // Remove Alpine.js import if it was added by Bonsai
                if (str_contains($content, "import alpine from 'alpinejs'")) {
                    // Only remove if it's likely added by Bonsai (near the PixelMatrix import or after the glob imports)
                    if ($isSage11Format || 
                        preg_match("/import\s+alpine\s+from\s+['\"](alpinejs|@alpinejs\/core)['\"];?\s*(\n|$)/", $content)) {
                        $content = preg_replace("/import\s+alpine\s+from\s+['\"](alpinejs|@alpinejs\/core)['\"];?\n?/", '', $content);
                        $modified = true;
                    }
                }
                
                // Remove the Alpine.js dark mode store and initialization
                if (str_contains($content, "alpine.store('darkMode'")) {
                    $pattern = "/document\.addEventListener\(['\"]alpine:init['\"]\s*,\s*\(\)\s*=>\s*{\s*\/\/\s*Add\s+dark\s+mode\s+store.*?}\);?\n?/s";
                    $content = preg_replace($pattern, '', $content);
                    $modified = true;
                }
                
                // Remove the Alpine.js start call if it was added by Bonsai
                if (str_contains($content, "// Start Alpine") && str_contains($content, "alpine.start()")) {
                    $content = preg_replace("/\/\/\s*Start\s+Alpine\s*\n\s*alpine\.start\(\);?\n?/", '', $content);
                    $modified = true;
                }
                
                // Clean up any double newlines
                $content = preg_replace("/\n{3,}/", "\n\n", $content);
                
                if ($modified) {
                    File::put($appJsPath, $content);
                    $this->line("- Removed Bonsai code from app.js (PixelMatrix, Alpine.js dark mode)");
                }
            } catch (\Exception $e) {
                $this->error("Failed to update app.js: " . $e->getMessage());
            }
        }
    }

    protected function cleanupBonsaiImages()
    {
        $this->info('Cleaning up Bonsai images...');
        
        $imagesDir = base_path('resources/images');
        if (!File::exists($imagesDir)) {
            return;
        }

        try {
            // Clean up all bonsai_ prefixed images
            $bonsaiImages = File::glob($imagesDir . '/bonsai_*.{webp,jpg,png,svg,gif}', GLOB_BRACE);
            foreach ($bonsaiImages as $file) {
                File::delete($file);
                $this->line("- Removed: " . basename($file));
            }
            
            // Also clean up specific known images that might not have the bonsai_ prefix
            $knownImages = [
                'hero_bg_dark.webp',
                'hero_bg_light.webp',
                'pixel-matrix-bg.svg',
            ];
            
            foreach ($knownImages as $imageName) {
                $imagePath = $imagesDir . '/' . $imageName;
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    $this->line("- Removed: " . $imageName);
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to clean up Bonsai images: " . $e->getMessage());
        }
    }
}