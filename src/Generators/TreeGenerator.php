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
        
        // Set random seed if provided
        if ($options['seed']) {
            srand($options['seed']);
        }
        
        // Create tree with specified style
        $tree = new BonsaiTree(
            $options['age'],
            $options['style'] ?? 'formal'
        );

        if ($this->debug) {
            echo "Generating tree with options: " . json_encode($options) . "\n";
        }

        return $tree;
    }

    public function age($tree)
    {
        // Age the tree by changing its style slightly
        $styles = ['formal', 'informal', 'slanting'];
        $currentIndex = array_search($tree->style, $styles);
        $tree->style = $styles[($currentIndex + 1) % count($styles)];
        $tree->updated_at = time();
        return $tree;
    }
} 