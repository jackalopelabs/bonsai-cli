<?php

namespace Jackalopelabs\BonsaiCli\Storage;

class BonsaiTreeStorage
{
    protected $trees = [];

    public function store($config, $tree)
    {
        $this->trees[$config] = $tree;
        return true;
    }

    public function get($config)
    {
        if (!isset($this->trees[$config])) {
            throw new \Exception("No tree found for config: {$config}");
        }
        return $this->trees[$config];
    }

    public function exists($config)
    {
        return isset($this->trees[$config]);
    }

    public function all()
    {
        return $this->trees;
    }
} 