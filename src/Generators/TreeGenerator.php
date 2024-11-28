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
        'multiplier' => 5,  // Controls branch density
        'life' => 32       // Controls overall size
    ];

    const BRANCH_TYPE_TRUNK = 'trunk';
    const BRANCH_TYPE_SHOOT_LEFT = 'shootLeft';
    const BRANCH_TYPE_SHOOT_RIGHT = 'shootRight';
    const BRANCH_TYPE_DYING = 'dying';
    const BRANCH_TYPE_DEAD = 'dead';

    public function generate(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);
        
        if ($options['seed']) {
            srand($options['seed']);
        }

        $tree = new BonsaiTree($options['age'], $options['style']);
        
        // Initialize the growth grid
        $grid = [];
        $maxWidth = 60;
        $maxHeight = 20;
        
        for ($y = 0; $y < $maxHeight; $y++) {
            for ($x = 0; $x < $maxWidth; $x++) {
                $grid[$y][$x] = ' ';
            }
        }

        // Start growing from the bottom center
        $startX = (int)($maxWidth / 2);
        $startY = $maxHeight - 1;
        
        $this->growBranch($grid, $startX, $startY, self::BRANCH_TYPE_TRUNK, $options['life'], $options['multiplier']);
        
        $tree->setGrid($grid);
        return $tree;
    }

    protected function growBranch(&$grid, $x, $y, $type, $life, $multiplier)
    {
        $branches = 0;
        $maxBranches = $multiplier * 10;

        while ($life > 0 && $y >= 0) {
            $life--;
            
            // Calculate growth direction
            list($dx, $dy) = $this->calculateGrowthDirection($type, $life, $multiplier);
            
            // Update position
            $x += $dx;
            $y += $dy;
            
            // Ensure we stay within bounds
            if ($x < 0 || $x >= count($grid[0]) || $y < 0 || $y >= count($grid)) {
                break;
            }

            // Add branch character
            $char = $this->chooseBranchCharacter($type, $dx, $dy);
            $grid[$y][$x] = $char;

            // Chance to create new branches
            if ($branches < $maxBranches && $life > 4) {
                if (rand(0, 10) < $multiplier) {
                    $branchType = (rand(0, 1) == 0) ? self::BRANCH_TYPE_SHOOT_LEFT : self::BRANCH_TYPE_SHOOT_RIGHT;
                    $this->growBranch($grid, $x, $y, $branchType, $life - 2, $multiplier);
                    $branches++;
                }
            }

            // Create leaves at branch ends
            if ($life < 3) {
                $this->addLeaves($grid, $x, $y);
            }
        }
    }

    protected function calculateGrowthDirection($type, $life, $multiplier)
    {
        $dx = 0;
        $dy = 0;

        switch ($type) {
            case self::BRANCH_TYPE_TRUNK:
                $dy = -1;  // Grow upward
                $dx = rand(-1, 1);  // Slight random horizontal movement
                break;

            case self::BRANCH_TYPE_SHOOT_LEFT:
                $dy = rand(0, 1) ? -1 : 0;
                $dx = rand(0, 2) == 0 ? -1 : 0;
                break;

            case self::BRANCH_TYPE_SHOOT_RIGHT:
                $dy = rand(0, 1) ? -1 : 0;
                $dx = rand(0, 2) == 0 ? 1 : 0;
                break;

            case self::BRANCH_TYPE_DYING:
                $dy = 0;
                $dx = rand(-1, 1);
                break;
        }

        return [$dx, $dy];
    }

    protected function chooseBranchCharacter($type, $dx, $dy)
    {
        switch ($type) {
            case self::BRANCH_TYPE_TRUNK:
                return $dy < 0 ? '|' : ($dx < 0 ? '\\' : '/');
            case self::BRANCH_TYPE_SHOOT_LEFT:
            case self::BRANCH_TYPE_SHOOT_RIGHT:
                return $dx == 0 ? '|' : ($dx < 0 ? '\\' : '/');
            case self::BRANCH_TYPE_DYING:
                return '~';
            default:
                return '|';
        }
    }

    protected function addLeaves(&$grid, $x, $y)
    {
        $leafChars = ['*', '&', '^', '@'];
        $positions = [
            [$x-1, $y], [$x+1, $y],
            [$x, $y-1], [$x, $y+1]
        ];

        foreach ($positions as [$leafX, $leafY]) {
            if (isset($grid[$leafY][$leafX]) && $grid[$leafY][$leafX] == ' ') {
                $grid[$leafY][$leafX] = $leafChars[array_rand($leafChars)];
            }
        }
    }
} 