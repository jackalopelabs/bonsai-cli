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
        $width = 0;  // Track trunk width
        
        while ($height < $maxHeight && $y > 0) {
            // More natural trunk growth
            if ($height < $maxHeight * 0.3) {
                // Bottom third: grow wider
                $dx = (rand(0, 10) < 7) ? (rand(0, 1) ? 1 : -1) : 0;
                $width = max($width, abs($dx));
            } else {
                // Upper parts: maintain width but allow slight variation
                $dx = (rand(0, 10) < 3) ? (rand(0, 1) ? 1 : -1) : 0;
            }
            
            $y--;
            $x += $dx;
            
            // Add trunk character with proper angle
            $char = $this->getTrunkChar($dx, $height);
            $grid[$y][$x] = $char;
            
            // Add supporting trunk characters for thickness
            for ($w = -$width; $w <= $width; $w++) {
                if ($w != 0 && isset($grid[$y][$x + $w])) {
                    $grid[$y][$x + $w] = $this->getTrunkChar($w, $height);
                }
            }

            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
            
            // Branch out more naturally
            if ($height > 3 && rand(0, 10) < $multiplier) {
                $branchLife = (int)($life * 0.4);
                if (rand(0, 1)) {
                    $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_LEFT, $branchLife, $multiplier);
                }
                if (rand(0, 1)) {
                    $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_RIGHT, $branchLife, $multiplier);
                }
            }
            
            $height++;
        }
        
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
        
        while ($length < $maxLength && $y > 0) {
            list($dx, $dy) = $this->calculateBranchDirection($type, $length);
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0 || $y >= count($grid)) break;
            
            $grid[$y][$x] = $this->chooseBranchCharacter($type, $dx, $dy);
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
            
            if ($length > 2 && rand(0, 10) < 3) {
                $this->addLeavesAnimated($tree, $x, $y);
            }
            
            $length++;
        }
        
        $this->addLeavesAnimated($tree, $x, $y);
    }

    protected function calculateBranchDirection($type, $length)
    {
        if ($type === self::BRANCH_TYPE_SHOOT_LEFT) {
            return [
                rand(0, 2) == 0 ? -1 : 0,
                rand(0, 2) == 0 ? -1 : 0
            ];
        } else {
            return [
                rand(0, 2) == 0 ? 1 : 0,
                rand(0, 2) == 0 ? -1 : 0
            ];
        }
    }

    protected function chooseBranchCharacter($type, $dx, $dy)
    {
        if ($dx == 0 && $dy == 0) return '|';
        if ($dx < 0) return '\\';
        if ($dx > 0) return '/';
        return '|';
    }

    protected function addLeavesAnimated(&$tree, $x, $y)
    {
        $leafChars = ['*', '&', '^'];
        $positions = [
            [$x-1, $y], [$x+1, $y],
            [$x, $y-1],
            [$x-1, $y-1], [$x+1, $y-1]
        ];

        $grid = $tree->getGrid();
        $changed = false;

        foreach ($positions as [$leafX, $leafY]) {
            if (isset($grid[$leafY][$leafX]) && $grid[$leafY][$leafX] == ' ') {
                if (rand(0, 2) == 0) {
                    $grid[$leafY][$leafX] = $leafChars[array_rand($leafChars)];
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
        }
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