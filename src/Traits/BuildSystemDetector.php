<?php

namespace Jackalopelabs\BonsaiCli\Traits;

trait BuildSystemDetector
{
    /**
     * Get the base path of the application
     *
     * @return string
     */
    protected function getBasePath()
    {
        // If base_path() function exists (in Laravel/Acorn context), use it
        if (function_exists('base_path')) {
            return base_path();
        }
        
        // Otherwise, try to determine the base path from the current directory
        // This is a fallback for standalone scripts
        return defined('ABSPATH') ? ABSPATH : getcwd();
    }

    /**
     * Detect whether the project uses Vite or Bud
     *
     * @return string 'vite', 'bud', or 'unknown'
     */
    protected function detectBuildSystem()
    {
        $basePath = $this->getBasePath();
        
        // Check for Vite configuration files
        if (file_exists($basePath . '/vite.config.js') || file_exists($basePath . '/vite.config.ts')) {
            return 'vite';
        }

        // Check for Bud configuration files
        if (file_exists($basePath . '/bud.config.js') || file_exists($basePath . '/bud.config.ts')) {
            return 'bud';
        }

        // Default to unknown if neither is found
        return 'unknown';
    }

    /**
     * Get the build command for the detected build system
     *
     * @param string $environment 'development', 'production', etc.
     * @return string The build command
     */
    protected function getBuildCommand($environment = 'production')
    {
        $buildSystem = $this->detectBuildSystem();

        if ($buildSystem === 'vite') {
            return "yarn build";
        } elseif ($buildSystem === 'bud') {
            return "yarn bud build {$environment}";
        }

        // Default command if build system is unknown
        return "yarn build";
    }

    /**
     * Get the asset directory for the detected build system
     *
     * @return string The asset directory path
     */
    protected function getAssetDirectory()
    {
        $buildSystem = $this->detectBuildSystem();

        if ($buildSystem === 'vite') {
            return 'public/build';
        } elseif ($buildSystem === 'bud') {
            return 'public/dist';
        }

        // Default directory if build system is unknown
        return 'public';
    }
} 