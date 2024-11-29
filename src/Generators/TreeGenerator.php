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
        $age = 0;
        $grid = $tree->getGrid();
        $shoots = 0;
        $maxShoots = $multiplier;
        
        while ($life > 0 && $y > 0) {
            $life--;
            $age++;
            
            // Calculate growth direction based on cbonsai's algorithm
            if ($age <= 2 || $life < 4) {
                $dy = 0;
                $dx = rand(0, 2) - 1;
            }
            // Young trunk grows wide
            elseif ($age < ($multiplier * 3)) {
                if ($age % (int)($multiplier * 0.5) == 0) {
                    $dy = -1;
                } else {
                    $dy = 0;
                }
                
                $dice = rand(0, 9);
                if ($dice == 0) $dx = -2;
                elseif ($dice >= 1 && $dice <= 3) $dx = -1;
                elseif ($dice >= 4 && $dice <= 5) $dx = 0;
                elseif ($dice >= 6 && $dice <= 8) $dx = 1;
                else $dx = 2;
            }
            // Middle-aged trunk
            else {
                $dy = rand(0, 10) > 2 ? -1 : 0;
                $dx = rand(0, 2) - 1;
            }
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0) continue;
            
            // Choose character based on growth direction
            $char = $this->getTrunkChar($dx, $dy);
            $grid[$y][$x] = $char;
            
            // Branch logic from cbonsai
            if ($life > 5 && $shoots < $maxShoots && 
                $age > 4 && rand(0, 15 - $multiplier) == 0) {
                $shootLife = $life + $multiplier - 2;
                $shootType = ($shoots == 0) ? 
                    (rand(0, 1) ? 'shootLeft' : 'shootRight') :
                    ($shoots % 2 ? 'shootLeft' : 'shootRight');
                    
                $this->growBranchAnimated($tree, $x, $y, $shootType, $shootLife, $multiplier);
                $shoots++;
            }
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
        }
    }

    protected function getTrunkChar($dx, $dy)
    {
        if ($dy == 0) {
            if ($dx < -1) return '\\\\';
            if ($dx == -1) return '\\';
            if ($dx == 0) return '|';
            if ($dx == 1) return '/';
            return '//';
        }
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
        $grid = $tree->getGrid();
        
        while ($life > 0) {
            $life--;
            
            // Branch growth patterns from cbonsai
            if ($type == 'shootLeft') {
                $dice = rand(0, 9);
                if ($dice <= 1) $dx = -2;
                elseif ($dice <= 5) $dx = -1;
                elseif ($dice <= 8) $dx = 0;
                else $dx = 1;
            } else {
                $dice = rand(0, 9);
                if ($dice <= 1) $dx = 2;
                elseif ($dice <= 5) $dx = 1;
                elseif ($dice <= 8) $dx = 0;
                else $dx = -1;
            }
            
            $dice = rand(0, 9);
            if ($dice <= 1) $dy = -1;
            elseif ($dice <= 7) $dy = 0;
            else $dy = 1;
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0 || $y >= count($grid)) break;
            
            $char = $this->getBranchChar($dx, $dy, $life);
            $grid[$y][$x] = $char;
            
            if ($life < 3) {
                $this->addLeafCluster($tree, $x, $y, 2);
            }
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
        }
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