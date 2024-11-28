<?php

namespace Jackalopelabs\BonsaiCli\Generators;

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
        
        // For initial testing, let's create a simple ASCII tree
        $tree = new \stdClass();
        $tree->age = $options['age'];
        $tree->style = $options['style'];
        $tree->created_at = now();
        $tree->updated_at = now();
        
        // Add a render method to the tree object
        $tree->render = function() {
            return "
    ^
   ^^^
  ^^^^^
 ^^^^^^^
   |||
   |||
  =====
";
        };

        if ($this->debug) {
            echo "Generating tree with options: " . json_encode($options) . "\n";
        }

        return $tree;
    }

    public function age($tree)
    {
        // For now, just return the same tree with updated timestamp
        $tree->updated_at = now();
        return $tree;
    }
} 