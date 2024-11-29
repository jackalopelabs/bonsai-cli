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
        
        // Adjust grid proportions
        $grid = [];
        $maxWidth = 50;    // Slightly smaller width
        $maxHeight = 12;   // Much shorter height
        
        for ($y = 0; $y < $maxHeight; $y++) {
            for ($x = 0; $x < $maxWidth; $x++) {
                $grid[$y][$x] = ' ';
            }
        }

        $startX = (int)($maxWidth / 2);
        $startY = $maxHeight - 1;
        
        $tree->setGrid($grid);
        
        // Reduce life to control height
        $this->growTrunkAnimated($tree, $startX, $startY, min($options['life'], 16), $options['multiplier']);
        
        return $tree;
    }

    protected function growTrunkAnimated(&$tree, $x, $y, $life, $multiplier)
    {
        $age = 0;
        $grid = $tree->getGrid();
        $shoots = 0;
        $maxShoots = $multiplier;  // Reduced from multiplier * 2
        $branchCount = 0;
        $maxBranches = $multiplier * 50;  // Reduced from 150
        $type = self::BRANCH_TYPE_TRUNK;
        $lifeStart = $life;
        $trunkWidth = 2;  // Reduced from 3
        
        while ($life > 0 && $y > 2) {  // Stop before reaching top
            $life--;
            $age++;
            
            // Base growth (first 30% of height)
            if ($age < ($lifeStart * 0.3)) {
                $dy = -1;
                $dx = (rand(0, 10) < 3) ? (rand(0, 1) ? 1 : -1) : 0;
                
                // Draw thick trunk
                for ($w = -$trunkWidth; $w <= $trunkWidth; $w++) {
                    $newX = $x + $w;
                    if (isset($grid[$y][$newX])) {
                        $grid[$y][$newX] = '|';
                    }
                }
                
                // Add early branches
                if (rand(0, 10) < 3) {  // Reduced frequency
                    $this->growBranchAnimated($tree, $x - $trunkWidth, $y, self::BRANCH_TYPE_SHOOT_LEFT, $life * 0.4, $multiplier);
                    $this->growBranchAnimated($tree, $x + $trunkWidth, $y, self::BRANCH_TYPE_SHOOT_RIGHT, $life * 0.4, $multiplier);
                }
            }
            // Middle growth (30-70% of height)
            elseif ($age < ($lifeStart * 0.7)) {
                $dy = -1;
                $dx = (rand(0, 10) < 4) ? (rand(0, 1) ? 1 : -1) : 0;
                $trunkWidth = max(1, $trunkWidth - 0.2);
                
                if (rand(0, 10) < 5) {  // Reduced frequency
                    $branchSide = rand(0, 1) ? self::BRANCH_TYPE_SHOOT_LEFT : self::BRANCH_TYPE_SHOOT_RIGHT;
                    $this->growBranchAnimated($tree, $x, $y, $branchSide, $life * 0.5, $multiplier);
                }
            }
            // Top growth (last 30%)
            else {
                $dy = -1;
                $dx = (rand(0, 10) < 5) ? (rand(0, 1) ? 1 : -1) : 0;
                
                if (rand(0, 10) < 6) {  // Slightly reduced
                    $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_LEFT, $life * 0.3, $multiplier);
                    $this->growBranchAnimated($tree, $x, $y, self::BRANCH_TYPE_SHOOT_RIGHT, $life * 0.3, $multiplier);
                }
            }
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0) continue;
            
            $grid[$y][$x] = $this->getTrunkChar($dx, $dy);
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
        }
        
        // Add final crown
        $this->addLeafCluster($tree, $x, $y, 3);  // Reduced from 4
    }

    protected function getTrunkChar($dx, $dy)
    {
        if ($dy < 0) {
            return '|';  // Growing upward
        } elseif ($dx < 0) {
            return '\\'; // Growing left
        } elseif ($dx > 0) {
            return '/';  // Growing right
        } else {
            return '|';  // Growing straight
        }
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
        $grid = $tree->getGrid();
        $length = 0;
        $maxLength = (int)($life * 0.7);  // Longer branches
        
        while ($length < $maxLength) {
            // More natural branch patterns
            $dx = ($type == self::BRANCH_TYPE_SHOOT_LEFT) ? 
                (rand(0, 10) < 7 ? -1 : 0) : 
                (rand(0, 10) < 7 ? 1 : 0);
            
            $dy = (rand(0, 10) < 6) ? -1 : 0;  // More upward growth
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0) break;
            
            $grid[$y][$x] = $this->getBranchChar($dx, $dy, $life);
            
            // Add leaves more frequently
            if ($length > ($maxLength * 0.5) && rand(0, 10) < 4) {
                $this->addLeafCluster($tree, $x, $y, 2);
            }
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
            $length++;
        }
        
        // Always end with leaves
        $this->addLeafCluster($tree, $x, $y, 3);
    }

    protected function getBranchChar($dx, $dy, $life)
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