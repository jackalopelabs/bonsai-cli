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

    public function generate(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);
        
        if ($options['seed']) {
            srand($options['seed']);
        }

        $tree = new BonsaiTree($options['age'], $options['style']);
        
        // Smaller grid for more controlled growth
        $grid = [];
        $maxWidth = 40;  // Reduced from 60
        $maxHeight = 15; // Reduced from 20
        
        for ($y = 0; $y < $maxHeight; $y++) {
            for ($x = 0; $x < $maxWidth; $x++) {
                $grid[$y][$x] = ' ';
            }
        }

        $startX = (int)($maxWidth / 2);
        $startY = $maxHeight - 1;
        
        // Start with a strong trunk
        $this->growTrunk($grid, $startX, $startY, $options['life'], $options['multiplier']);
        
        $tree->setGrid($grid);
        return $tree;
    }

    protected function growTrunk(&$grid, $x, $y, $life, $multiplier)
    {
        $height = 0;
        $maxHeight = (int)($life * 0.7); // Trunk uses 70% of life
        
        while ($height < $maxHeight && $y > 0) {
            // Trunk grows mostly straight up with slight variation
            $dx = (rand(0, 10) < 8) ? 0 : (rand(0, 1) ? 1 : -1);
            $y--;
            $x += $dx;
            
            if ($x < 0 || $x >= count($grid[0])) continue;
            
            $grid[$y][$x] = '|';
            
            // Add branches every few steps
            if ($height > 2 && $height % 2 == 0) {
                $branchLife = (int)($life * 0.3); // Branches get 30% of life
                if (rand(0, 1)) {
                    $this->growBranch($grid, $x, $y, self::BRANCH_TYPE_SHOOT_LEFT, $branchLife, $multiplier);
                }
                if (rand(0, 1)) {
                    $this->growBranch($grid, $x, $y, self::BRANCH_TYPE_SHOOT_RIGHT, $branchLife, $multiplier);
                }
            }
            
            $height++;
        }
        
        // Add crown at the top
        $this->addCrown($grid, $x, $y);
    }

    protected function growBranch(&$grid, $x, $y, $type, $life, $multiplier)
    {
        $length = 0;
        $maxLength = (int)($life * 0.5);
        
        while ($length < $maxLength && $y > 0) {
            list($dx, $dy) = $this->calculateBranchDirection($type, $length);
            
            $x += $dx;
            $y += $dy;
            
            if ($x < 0 || $x >= count($grid[0]) || $y < 0 || $y >= count($grid)) break;
            
            $grid[$y][$x] = $this->chooseBranchCharacter($type, $dx, $dy);
            
            if ($length > 2 && rand(0, 10) < 3) {
                $this->addLeaves($grid, $x, $y);
            }
            
            $length++;
        }
        
        // Add leaves at branch end
        $this->addLeaves($grid, $x, $y);
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

    protected function addLeaves(&$grid, $x, $y)
    {
        $leafChars = ['*', '&', '^'];
        $positions = [
            [$x-1, $y], [$x+1, $y],
            [$x, $y-1],
            [$x-1, $y-1], [$x+1, $y-1]
        ];

        foreach ($positions as [$leafX, $leafY]) {
            if (isset($grid[$leafY][$leafX]) && $grid[$leafY][$leafX] == ' ') {
                if (rand(0, 2) == 0) { // Only add leaves sometimes
                    $grid[$leafY][$leafX] = $leafChars[array_rand($leafChars)];
                }
            }
        }
    }

    protected function addCrown(&$grid, $x, $y)
    {
        $leafChars = ['*', '&', '^'];
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
    }
} 