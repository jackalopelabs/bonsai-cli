<?php

namespace Jackalopelabs\BonsaiCli\Generators;

use Illuminate\Support\Str;

class TreeGenerator
{
    protected $debug = false;
    protected $width = 60;
    protected $height = 25;
    
    protected $leafChars = [
        'spring' => ['❀', '✿', '♠'],
        'summer' => ['☘', '❦', '❧'],
        'fall' => ['✾', '❁', '⚘'],
        'winter' => ['❄', '❆', '❅'],
    ];

    protected $branchChars = ['│', '─', '┌', '┐', '└', '┘', '├', '┤', '┬', '┴', '┼'];
    
    public function enableDebug()
    {
        $this->debug = true;
    }

    public function generate(array $options = [])
    {
        $season = $options['season'] ?? $this->getCurrentSeason();
        $age = $options['age'] ?? 'mature';
        $style = $options['style'] ?? 'formal';
        $seed = $options['seed'] ?? random_int(1, 999999);

        // Set random seed for consistent generation
        mt_srand($seed);

        $this->log("Generating tree with options:", $options);

        // Initialize the canvas
        $canvas = $this->initializeCanvas();

        // Generate the base trunk
        $trunk = $this->generateTrunk($style, $age);
        $this->applyToCanvas($canvas, $trunk);

        // Generate branches based on style and age
        $branches = $this->generateBranches($style, $age);
        $this->applyToCanvas($canvas, $branches);

        // Add leaves based on season
        $leaves = $this->generateLeaves($season, $age);
        $this->applyToCanvas($canvas, $leaves);

        // Create and return the tree object
        return new BonsaiTree([
            'canvas' => $canvas,
            'age' => $age,
            'style' => $style,
            'season' => $season,
            'seed' => $seed,
        ]);
    }

    public function age(BonsaiTree $tree)
    {
        $currentAge = $tree->age;
        $newAge = $this->calculateNewAge($currentAge);
        
        // Generate new tree with aged characteristics
        return $this->generate([
            'age' => $newAge,
            'style' => $tree->style,
            'season' => $tree->season,
            'seed' => $tree->seed,
        ]);
    }

    protected function getCurrentSeason()
    {
        $month = (int) date('n');
        $isNorthern = config('bonsai.seasonal.hemisphereNorth', true);
        
        if ($isNorthern) {
            return match(true) {
                $month >= 3 && $month <= 5 => 'spring',
                $month >= 6 && $month <= 8 => 'summer',
                $month >= 9 && $month <= 11 => 'fall',
                default => 'winter',
            };
        } else {
            return match(true) {
                $month >= 3 && $month <= 5 => 'fall',
                $month >= 6 && $month <= 8 => 'winter',
                $month >= 9 && $month <= 11 => 'spring',
                default => 'summer',
            };
        }
    }

    protected function initializeCanvas()
    {
        $canvas = array_fill(0, $this->height, array_fill(0, $this->width, ' '));
        $this->log("Initialized canvas: {$this->width}x{$this->height}");
        return $canvas;
    }

    protected function generateTrunk($style, $age)
    {
        $trunk = [];
        $baseHeight = match($age) {
            'young' => (int)($this->height * 0.4),
            'ancient' => (int)($this->height * 0.8),
            default => (int)($this->height * 0.6),
        };

        // Calculate trunk curve based on style
        $curve = match($style) {
            'slanting' => 0.3,
            'cascade' => -0.5,
            'informal' => 0.15,
            default => 0,
        };

        for ($y = $this->height - 1; $y > $this->height - $baseHeight; $y--) {
            $x = (int)($this->width / 2 + $curve * ($this->height - $y));
            $trunk[] = ['x' => $x, 'y' => $y, 'char' => '│'];
        }

        $this->log("Generated trunk with style: {$style}, age: {$age}");
        return $trunk;
    }

    protected function generateBranches($style, $age)
    {
        $branches = [];
        $branchCount = match($age) {
            'young' => mt_rand(2, 4),
            'ancient' => mt_rand(6, 10),
            default => mt_rand(4, 7),
        };

        $this->log("Generating {$branchCount} branches");

        // Generate main branches
        for ($i = 0; $i < $branchCount; $i++) {
            $branches = array_merge(
                $branches,
                $this->generateBranch($style, $age, $i)
            );
        }

        return $branches;
    }

    protected function generateBranch($style, $age, $index)
    {
        $branch = [];
        $length = match($age) {
            'young' => mt_rand(3, 6),
            'ancient' => mt_rand(8, 15),
            default => mt_rand(5, 10),
        };

        // Calculate branch direction and angle based on style
        $direction = $index % 2 === 0 ? 1 : -1;
        $angle = match($style) {
            'formal' => 0.2,
            'slanting' => 0.4,
            'cascade' => -0.3,
            default => 0.3,
        };

        for ($i = 0; $i < $length; $i++) {
            $branch[] = [
                'x' => $i * $direction,
                'y' => (int)($i * $angle),
                'char' => $i === 0 ? '├' : '─',
            ];
        }

        return $branch;
    }

    protected function generateLeaves($season, $age)
    {
        $leaves = [];
        $leafDensity = match($age) {
            'young' => 0.3,
            'ancient' => 0.8,
            default => 0.5,
        };

        // Adjust density based on season
        if ($season === 'winter') {
            $leafDensity *= 0.3;
        }

        $leafChars = $this->leafChars[$season];
        
        // Add leaves around branches
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (mt_rand() / mt_getrandmax() < $leafDensity) {
                    $leaves[] = [
                        'x' => $x,
                        'y' => $y,
                        'char' => $leafChars[array_rand($leafChars)],
                    ];
                }
            }
        }

        return $leaves;
    }

    protected function applyToCanvas(&$canvas, $elements)
    {
        foreach ($elements as $element) {
            $x = $element['x'];
            $y = $element['y'];
            
            // Ensure coordinates are within bounds
            if ($x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height) {
                $canvas[$y][$x] = $element['char'];
            }
        }
    }

    protected function calculateNewAge($currentAge)
    {
        return match($currentAge) {
            'young' => 'mature',
            'mature' => 'ancient',
            default => 'ancient',
        };
    }

    protected function log($message, $context = [])
    {
        if ($this->debug) {
            $contextStr = empty($context) ? '' : ': ' . json_encode($context);
            echo "[DEBUG] {$message}{$contextStr}\n";
        }
    }

    protected function generateBranch($x, $y, $type, $life, $age = 0)
    {
        $branches = [];
        
        while ($life > 0) {
            $life--;
            $age = $this->lifeStart - $life;
            
            // Get movement deltas based on type and age
            [$dx, $dy] = $this->calculateDeltas($type, $life, $age);
            
            // Branch creation logic
            if ($life < 3) {
                $branches = array_merge(
                    $branches,
                    $this->generateBranch($x, $y, 'dead', $life)
                );
            } 
            else if ($type === 'trunk' && $life < ($this->multiplier + 2)) {
                $branches = array_merge(
                    $branches,
                    $this->generateBranch($x, $y, 'dying', $life)
                );
            }
            
            // Random trunk branching
            else if ($type === 'trunk' && 
                    (mt_rand(0, 2) === 0 || ($life % $this->multiplier === 0))) {
                    
                if (mt_rand(0, 7) === 0 && $life > 7) {
                    // Create new trunk
                    $branches = array_merge(
                        $branches,
                        $this->generateBranch($x, $y, 'trunk', $life + mt_rand(-2, 2))
                    );
                }
            }
            
            // Update position
            $x += $dx;
            $y += $dy;
            
            // Add branch segment
            $branches[] = [
                'x' => $x,
                'y' => $y,
                'char' => $this->getBranchChar($type, $dx, $dy),
                'type' => $type
            ];
        }
        
        return $branches;
    }
    
    protected function calculateDeltas($type, $life, $age)
    {
        // Port of cbonsai's setDeltas() function
        $dx = 0;
        $dy = 0;
        
        switch ($type) {
            case 'trunk':
                if ($age <= 2 || $life < 4) {
                    $dy = 0;
                    $dx = mt_rand(-1, 1);
                }
                else if ($age < ($this->multiplier * 3)) {
                    $dy = ($age % (int)($this->multiplier * 0.5) === 0) ? -1 : 0;
                    
                    $chance = mt_rand(0, 9);
                    $dx = match(true) {
                        $chance <= 0 => -2,
                        $chance <= 3 => -1,
                        $chance <= 5 => 0,
                        $chance <= 8 => 1,
                        default => 2
                    };
                }
                // ... rest of the delta calculations
            break;
            
            // ... other cases
        }
        
        return [$dx, $dy];
    }
} 