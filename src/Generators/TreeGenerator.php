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
        'multiplier' => 5,
        'life' => 32
    ];

    const BRANCH_TYPE_TRUNK = 'trunk';
    const BRANCH_TYPE_SHOOT_LEFT = 'shootLeft';
    const BRANCH_TYPE_SHOOT_RIGHT = 'shootRight';
    const BRANCH_TYPE_DYING = 'dying';

    protected $growthCallback = null;

    protected $branchChars = [
        'trunk' => ['|', '/', '\\'],
        'branch' => ['/', '\\', '|', '~'],
        'dying' => ['~', '_'],
        'leaves' => ['&', ' &', '& ', '&&', ' && ', '&&&']
    ];

    public function onGrowth(callable $callback)
    {
        $this->growthCallback = $callback;
    }

    protected function notifyGrowth($tree)
    {
        if ($this->growthCallback) {
            call_user_func($this->growthCallback, $tree);
        }
    }

    public function generate(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);
        
        if ($options['seed']) {
            srand($options['seed']);
        }

        $tree = new BonsaiTree($options['age'], $options['style']);
        
        $grid = [];
        $maxWidth = 40;
        $maxHeight = 15;
        
        for ($y = 0; $y < $maxHeight; $y++) {
            for ($x = 0; $x < $maxWidth; $x++) {
                $grid[$y][$x] = ' ';
            }
        }

        $startX = (int)($maxWidth / 2);
        $startY = $maxHeight - 1;
        
        $tree->setGrid($grid);
        
        // Animate trunk growth
        $this->growTrunkAnimated($tree, $startX, $startY, $options['life'], $options['multiplier']);
        
        return $tree;
    }

    protected function growTrunkAnimated(&$tree, $x, $y, $life, $multiplier)
    {
        $height = 0;
        $maxHeight = (int)($life * 0.7);
        $grid = $tree->getGrid();
        
        // Start with a strong, mostly vertical trunk
        while ($height < $maxHeight && $y > 0) {
            $y--;
            
            // Trunk should mostly grow straight up with slight variations
            if ($height < $maxHeight * 0.2) {
                // Base: very stable
                $dx = 0;
            } else {
                // Upper: slight variation
                $dx = (rand(0, 10) < 2) ? (rand(0, 1) ? 1 : -1) : 0;
            }
            
            $x += $dx;
            
            if ($x < 0 || $x >= count($grid[0])) continue;
            
            // Place trunk character
            $grid[$y][$x] = '|';
            
            // Add branches more naturally
            if ($height > 2) {
                $branchChance = $height < ($maxHeight * 0.3) ? 2 : 4; // More branches higher up
                
                if (rand(0, 10) < $branchChance) {
                    // Left branch
                    $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_LEFT, $life * 0.4, $multiplier);
                }
                if (rand(0, 10) < $branchChance) {
                    // Right branch
                    $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_RIGHT, $life * 0.4, $multiplier);
                }
            }
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
            $height++;
        }
        
        // Add final crown of leaves
        $this->addLeafClusters($tree, $x, $y);
    }

    protected function getTrunkChar($dx, $height)
    {
        if ($dx < 0) return '\\';
        if ($dx > 0) return '/';
        return '|';
    }

    protected function addLeafClusters(&$tree, $x, $y)
    {
        $grid = $tree->getGrid();
        $leafPattern = $this->branchChars['leaves'];
        
        // Create natural-looking leaf clusters
        for ($dy = -2; $dy <= 2; $dy++) {
            $width = 3 - abs($dy);  // Wider in middle, narrower at top/bottom
            for ($dx = -$width; $dx <= $width; $dx++) {
                $newX = $x + $dx;
                $newY = $y + $dy;
                
                if (isset($grid[$newY][$newX]) && $grid[$newY][$newX] == ' ') {
                    if (rand(0, 10) < 7) {  // 70% chance of leaf
                        $grid[$newY][$newX] = $leafPattern[array_rand($leafPattern)];
                    }
                }
            }
        }
        
        $tree->setGrid($grid);
        $this->notifyGrowth($tree);
    }

    protected function growBranchAnimated(&$tree, $x, $y, $type, $life, $multiplier)
    {
        $length = 0;
        $maxLength = (int)($life * 0.5);
        $grid = $tree->getGrid();
        $direction = $type === self::BRANCH_TYPE_SHOOT_LEFT ? -1 : 1;
        
        while ($length < $maxLength) {
            // Branch growth pattern
            $dx = (rand(0, 10) < 7) ? $direction : 0;  // Mostly grow in branch direction
            $dy = (rand(0, 10) < 3) ? -1 : 0;         // Occasionally grow upward
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0 || $y >= count($grid)) break;
            
            // Choose appropriate character based on growth direction
            $char = $dx === 0 ? '|' : ($dx < 0 ? '\\' : '/');
            $grid[$y][$x] = $char;
            
            // Add leaves near branch ends
            if ($length > ($maxLength * 0.7)) {
                $this->addLeafCluster($tree, $x, $y, 2); // Small clusters
            }
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
            $length++;
        }
        
        // Add final leaf cluster at branch end
        $this->addLeafCluster($tree, $x, $y, 3); // Larger end cluster
    }

    protected function addLeafCluster(&$tree, $x, $y, $size)
    {
        $grid = $tree->getGrid();
        
        for ($dy = -$size; $dy <= $size; $dy++) {
            for ($dx = -$size; $dx <= $size; $dx++) {
                // Skip if too far from center (make circular clusters)
                if ($dx * $dx + $dy * $dy > $size * $size) continue;
                
                $newX = $x + $dx;
                $newY = $y + $dy;
                
                if (isset($grid[$newY][$newX]) && $grid[$newY][$newX] == ' ') {
                    if (rand(0, 10) < 6) {  // 60% chance of leaf
                        $grid[$newY][$newX] = '&';
                    }
                }
            }
        }
        
        $tree->setGrid($grid);
        $this->notifyGrowth($tree);
    }

    protected function addCrownAnimated(&$tree, $x, $y)
    {
        $leafChars = ['*', '&', '^'];
        $grid = $tree->getGrid();
        for ($dy = -1; $dy <= 1; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $newX = $x + $dx;
                $newY = $y + $dy;
                if (isset($grid[$newY][$newX]) && $grid[$newY][$newX] == ' ') {
                    if (rand(0, 1)) {
                        $grid[$newY][$newX] = $leafChars[array_rand($leafChars)];
                    }
                }
            }
        }
        $tree->setGrid($grid);
    }
} 