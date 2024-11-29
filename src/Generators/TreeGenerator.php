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
        $branchCount = 0;
        $maxBranches = $multiplier * 110;  // From cbonsai
        
        while ($life > 0 && $y > 0) {
            $life--;
            $age++;
            
            // Calculate growth direction based on cbonsai's exact algorithm
            if ($age <= 2 || $life < 4) {
                $dy = 0;
                $dx = rand(0, 2) - 1;
            }
            elseif ($age < ($multiplier * 3)) {
                if ($age % (int)($multiplier * 0.5) == 0) {
                    $dy = -1;
                } else {
                    $dy = 0;
                }
                
                $dice = rand(0, 9);
                if ($dice <= 0) $dx = -2;
                elseif ($dice <= 3) $dx = -1;
                elseif ($dice <= 5) $dx = 0;
                elseif ($dice <= 8) $dx = 1;
                else $dx = 2;
            }
            else {
                $dice = rand(0, 9);
                $dy = ($dice > 2) ? -1 : 0;
                $dx = rand(0, 2) - 1;
            }
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0) continue;
            
            // Choose character based on growth direction
            $char = $this->getTrunkChar($dx, $dy);
            $grid[$y][$x] = $char;
            
            // Branch logic exactly as in cbonsai
            if ($branchCount < $maxBranches && 
                (($life < 3) || 
                ($type == 'trunk' && $life < ($lifeStart - 8) && 
                (rand(0, 15 - $multiplier) == 0 || 
                ($type == 'trunk' && $life % 5 == 0 && $life > 5))))) {
                
                if (rand(0, 2) == 0 && $life > 7) {
                    // Create another trunk
                    $this->growTrunkAnimated($tree, $x, $y, $life, $multiplier);
                } elseif ($shoots < $maxShoots) {
                    // Create a shoot
                    $shootLife = $life + $multiplier - 2;
                    if ($shootLife < 0) $shootLife = 0;
                    
                    $shootType = ($shoots == 0) ? 
                        (rand(0, 1) ? 'shootLeft' : 'shootRight') :
                        ($shoots % 2 ? 'shootRight' : 'shootLeft');
                    
                    $this->growBranchAnimated($tree, $x, $y, $shootType, $shootLife, $multiplier);
                    $shoots++;
                }
                $branchCount++;
            }
            
            $tree->setGrid($grid);
            $this->notifyGrowth($tree);
        }
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
        
        while ($life > 0) {
            $life--;
            
            // Branch growth patterns exactly from cbonsai
            if ($type == 'shootLeft') {
                $dice = rand(0, 9);
                if ($dice <= 1) $dx = -2;      // 20% far left
                elseif ($dice <= 5) $dx = -1;  // 40% left
                elseif ($dice <= 8) $dx = 0;   // 30% straight
                else $dx = 1;                  // 10% right
            } else {
                $dice = rand(0, 9);
                if ($dice <= 1) $dx = 2;       // 20% far right
                elseif ($dice <= 5) $dx = 1;   // 40% right
                elseif ($dice <= 8) $dx = 0;   // 30% straight
                else $dx = -1;                 // 10% left
            }
            
            $dice = rand(0, 9);
            if ($dice <= 1) $dy = -1;          // 20% up
            elseif ($dice <= 7) $dy = 0;       // 60% straight
            else $dy = 1;                      // 20% down
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0) break;
            
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