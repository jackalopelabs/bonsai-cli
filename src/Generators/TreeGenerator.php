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
        'trunk' => ['|', '/', '\\', '|'],
        'branch' => ['/', '\\', '|', '~'],
        'dying' => ['~', '_'],
        'leaves' => ['&']
    ];

    protected $baseArt = [
        ":___________./~~~\\.___________:",
        " \\                           /",
        "  \\_________________________/",
        "  (_)                     (_)"
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
        $maxHeight = (int)($life * 0.8);
        $grid = $tree->getGrid();
        
        // Start with a thick base
        $baseWidth = 3;
        $currentWidth = $baseWidth;
        
        while ($height < $maxHeight && $y > 0) {
            $y--;
            
            // Trunk behavior changes with height
            if ($height < $maxHeight * 0.2) {
                // Base: thick and mostly straight
                $dx = (rand(0, 10) < 2) ? (rand(0, 1) ? 1 : -1) : 0;
                for ($w = -$currentWidth; $w <= $currentWidth; $w++) {
                    if (isset($grid[$y][$x + $w])) {
                        $grid[$y][$x + $w] = '|';
                    }
                }
                if ($height % 3 == 0 && $currentWidth > 1) $currentWidth--;
            } else {
                // Upper trunk: more varied
                $dx = (rand(0, 10) < 3) ? (rand(0, 1) ? 1 : -1) : 0;
                $char = $this->getTrunkChar($dx);
                $grid[$y][$x] = $char;
                
                // Add branches more frequently in upper sections
                if ($height > $maxHeight * 0.3) {
                    if (rand(0, 10) < 6) {
                        $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_LEFT, $life * 0.4, $multiplier);
                    }
                    if (rand(0, 10) < 6) {
                        $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_RIGHT, $life * 0.4, $multiplier);
                    }
                }
            }
            
            $x += $dx;
            if ($x < 0 || $x >= count($grid[0])) continue;
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
            $height++;
        }
        
        // Add dense crown at the top
        $this->addCrown($tree, $x, $y);
    }

    protected function getTrunkChar($dx)
    {
        if ($dx < 0) return '\\';
        if ($dx > 0) return '/';
        return '|';
    }

    protected function addCrown(&$tree, $x, $y)
    {
        $grid = $tree->getGrid();
        $leafChars = ['*', '&', '^'];
        
        // Create natural-looking leaf clusters
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
        $this->notifyGrowth($tree);
    }

    protected function growBranchAnimated(&$tree, $x, $y, $type, $life, $multiplier)
    {
        $length = 0;
        $maxLength = (int)($life * 0.6);
        $grid = $tree->getGrid();
        $direction = $type === self::BRANCH_TYPE_SHOOT_LEFT ? -1 : 1;
        
        $dx = $direction;
        $dy = -1; // Start growing upward
        
        while ($length < $maxLength) {
            // More natural branch patterns
            if (rand(0, 10) < 3) {
                $dy = rand(0, 2) == 0 ? 0 : -1;  // Occasionally grow horizontal
            }
            if (rand(0, 10) < 2) {
                $dx = $direction * (rand(0, 1) ? 1 : 0);  // Occasionally grow straight
            }
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0 || $y >= count($grid)) break;
            
            $char = $this->getBranchChar($dx, $dy);
            $grid[$y][$x] = $char;
            
            // Add leaves along the branch
            if ($length > ($maxLength * 0.5) && rand(0, 10) < 4) {
                $this->addLeafCluster($tree, $x, $y, 2);
            }
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
            $length++;
        }
        
        // Add leaf cluster at branch end
        $this->addLeafCluster($tree, $x, $y, 3);
    }

    protected function getBranchChar($dx, $dy)
    {
        if ($dy == -1) {
            return $dx < 0 ? '\\' : '/';
        }
        return $dx < 0 ? '\\' : ($dx > 0 ? '/' : '|');
    }

    protected function addLeafCluster(&$tree, $x, $y, $size)
    {
        $grid = $tree->getGrid();
        
        for ($dy = -$size; $dy <= 0; $dy++) {
            $width = $size - abs($dy);
            for ($dx = -$width; $dx <= $width; $dx++) {
                $newX = $x + $dx;
                $newY = $y + $dy;
                
                if (isset($grid[$newY][$newX]) && $grid[$newY][$newX] == ' ') {
                    if (rand(0, 10) < 8) {  // 80% chance of leaf
                        $grid[$newY][$newX] = '&';
                    }
                }
            }
        }
        
        $tree->setGrid($grid);
        $this->notifyGrowth($tree);
    }
} 