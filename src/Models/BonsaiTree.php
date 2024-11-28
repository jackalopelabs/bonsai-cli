<?php

namespace Jackalopelabs\BonsaiCli\Models;

class BonsaiTree
{
    public $age;
    public $style;
    public $created_at;
    public $updated_at;

    // ANSI Color codes
    protected $colors = [
        'brown' => "\e[38;5;130m",      // trunk color
        'green' => "\e[38;5;106m",      // leaf color
        'light_green' => "\e[38;5;114m", // young leaf color
        'dark_brown' => "\e[38;5;94m",   // old trunk color
        'reset' => "\e[0m"              // reset color
    ];

    protected $leaves = ['&', '*', '^', '@', '%'];
    protected $trunk = ['/', '|', '\\'];
    protected $branches = ['/', '~', '\\', '|'];

    protected $grid = [];

    public function __construct($age, $style)
    {
        $this->age = $age;
        $this->style = $style;
        $this->created_at = time();
        $this->updated_at = time();
    }

    protected function colorize($text, $color)
    {
        return $this->colors[$color] . $text . $this->colors['reset'];
    }

    public function setGrid($grid)
    {
        $this->grid = $grid;
    }

    public function render()
    {
        if (empty($this->grid)) {
            return $this->renderStatic(); // Fallback to static rendering
        }

        $output = [];
        foreach ($this->grid as $row) {
            $line = '';
            foreach ($row as $cell) {
                if (in_array($cell, ['|', '\\', '/', '~'])) {
                    $line .= $this->colorize($cell, 'brown');
                } elseif (in_array($cell, ['*', '&', '^', '@'])) {
                    $line .= $this->colorize($cell, 'green');
                } else {
                    $line .= $cell;
                }
            }
            $output[] = $line;
        }

        // Add the base
        $output[] = $this->colorize("    =============    ", 'dark_brown');
        
        return implode("\n", $output);
    }

    protected function renderStatic()
    {
        $tree = [];
        
        switch ($this->style) {
            case 'formal':
                $tree = [
                    "          " . $this->colorize($this->leaves[2], 'light_green') . "          ",
                    "         " . $this->colorize($this->leaves[0] . $this->leaves[2] . $this->leaves[0], 'green') . "         ",
                    "     " . $this->colorize($this->leaves[1], 'green') . "  " . $this->colorize($this->leaves[2] . $this->leaves[0] . $this->leaves[2], 'light_green') . "  " . $this->colorize($this->leaves[1], 'green') . "     ",
                    "       " . $this->colorize($this->leaves[0], 'green') . $this->colorize("/" . $this->trunk[1] . "\\", 'brown') . $this->colorize($this->leaves[0], 'green') . "       ",
                    "        " . $this->colorize($this->trunk[1] . " " . $this->trunk[1], 'brown') . "        ",
                    "      " . $this->colorize($this->branches[1] . $this->trunk[1] . $this->trunk[1] . $this->trunk[1] . $this->branches[3], 'dark_brown') . "      ",
                    "     " . $this->colorize($this->branches[0] . $this->trunk[1] . $this->trunk[1] . " " . $this->trunk[1] . $this->trunk[1] . $this->branches[2], 'dark_brown') . "     ",
                    "    " . $this->colorize("=============", 'dark_brown') . "    "
                ];
                break;
                
            case 'informal':
                $tree = [
                    "           {$this->leaves[2]}        ",
                    "     {$this->leaves[0]}  {$this->leaves[2]}{$this->leaves[0]}{$this->leaves[2]}         ",
                    "   {$this->leaves[1]}  {$this->leaves[2]}{$this->leaves[0]}{$this->leaves[2]}  {$this->leaves[1]}     ",
                    "      {$this->leaves[0]}\\{$this->trunk[1]}/{$this->leaves[0]}       ",
                    "       {$this->trunk[1]} {$this->trunk[2]}        ",
                    "     {$this->branches[1]}{$this->trunk[1]}{$this->trunk[1]}{$this->branches[2]}{$this->branches[3]}      ",
                    "    {$this->branches[0]}{$this->trunk[1]}{$this->trunk[1]}{$this->trunk[1]}{$this->branches[2]}     ",
                    "    =============    "
                ];
                break;
                
            case 'slanting':
                $tree = [
                    "              {$this->leaves[2]}     ",
                    "        {$this->leaves[0]}  {$this->leaves[2]}{$this->leaves[0]}{$this->leaves[2]}      ",
                    "      {$this->leaves[1]}  {$this->leaves[2]}{$this->leaves[0]}{$this->leaves[2]}  {$this->leaves[1]}  ",
                    "        {$this->leaves[0]}\\{$this->trunk[2]}/{$this->leaves[0]}    ",
                    "         {$this->trunk[2]} {$this->trunk[2]}     ",
                    "       {$this->branches[1]}{$this->trunk[2]}{$this->trunk[2]}{$this->branches[2]}    ",
                    "      {$this->branches[0]}{$this->trunk[2]}{$this->trunk[2]}{$this->branches[2]}   ",
                    "    =============    "
                ];
                break;
        }
        
        return implode("\n", $tree);
    }
} 