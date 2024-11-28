<?php

namespace Jackalopelabs\BonsaiCli\Generators;

use Jackalopelabs\BonsaiCli\Models\BonsaiTree;

class TreeGenerator
{
    protected $debug = false;
    protected $defaultOptions = [
        'season' => 'spring',
        'age' => 'young',
        'style' => 'formal',
        'seed' => null,
    ];

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function generate(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);
        
        // Create a proper BonsaiTree instance instead of stdClass
        $tree = new BonsaiTree(
            $options['age'],
            $options['style']
        );

        if ($this->debug) {
            echo "Generating tree with options: " . json_encode($options) . "\n";
        }

        return $tree;
    }

    public function age($tree)
    {
        $tree->updated_at = time();
        return $tree;
    }
} 