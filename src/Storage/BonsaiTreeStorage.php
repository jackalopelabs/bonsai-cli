<?php

namespace Jackalopelabs\BonsaiCli\Storage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Jackalopelabs\BonsaiCli\Models\BonsaiTree;

class BonsaiTreeStorage
{
    protected $storagePath;
    protected $cachePrefix = 'bonsai_tree_';

    public function __construct()
    {
        $this->storagePath = config('bonsai.storage.path', storage_path('bonsai/trees'));
        
        if (!File::exists($this->storagePath)) {
            File::makeDirectory($this->storagePath, 0755, true);
        }
    }

    public function store(string $configPath, BonsaiTree $tree)
    {
        $key = $this->getKeyFromConfig($configPath);
        $data = $tree->toArray();
        
        // Store the tree data
        File::put(
            $this->getStoragePath($key),
            json_encode($data, JSON_PRETTY_PRINT)
        );

        // Cache the tree for quick access
        Cache::put($this->cachePrefix . $key, $data, now()->addHours(24));

        return true;
    }

    public function get(string $configPath): ?BonsaiTree
    {
        $key = $this->getKeyFromConfig($configPath);
        
        // Try to get from cache first
        $data = Cache::get($this->cachePrefix . $key);
        
        if (!$data) {
            $path = $this->getStoragePath($key);
            if (!File::exists($path)) {
                return null;
            }
            
            $data = json_decode(File::get($path), true);
            
            // Cache for future requests
            Cache::put($this->cachePrefix . $key, $data, now()->addHours(24));
        }

        return new BonsaiTree($data);
    }

    public function exists(string $configPath): bool
    {
        $key = $this->getKeyFromConfig($configPath);
        return File::exists($this->getStoragePath($key));
    }

    public function all(): array
    {
        $trees = [];
        $files = File::files($this->storagePath);

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $data = json_decode(File::get($file), true);
            $trees[$key] = new BonsaiTree($data);
        }

        return $trees;
    }

    public function delete(string $configPath): bool
    {
        $key = $this->getKeyFromConfig($configPath);
        $path = $this->getStoragePath($key);
        
        // Remove from cache
        Cache::forget($this->cachePrefix . $key);
        
        // Remove file if it exists
        if (File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    protected function getKeyFromConfig(string $configPath): string
    {
        return md5($configPath);
    }

    protected function getStoragePath(string $key): string
    {
        return $this->storagePath . '/' . $key . '.json';
    }
} 