<?php

namespace Jackalopelabs\BonsaiCli\Models;

class BonsaiTree
{
    public $age;
    public $style;
    public $created_at;
    public $updated_at;
    protected $leaves = ['&', '*', '^', '@', '%'];
    protected $trunk = ['/', '|', '\\'];
    protected $branches = ['/', '~', '\\', '|'];

    public function __construct($age, $style)
    {
        $this->age = $age;
        $this->style = $style;
        $this->created_at = time();
        $this->updated_at = time();
    }

    public function render()
    {
        $tree = [];
        
        // Add branches and leaves based on style
        switch ($this->style) {
            case 'formal':
                $tree = [
                    "          {$this->leaves[2]}          ",
                    "         {$this->leaves[0]}{$this->leaves[2]}{$this->leaves[0]}         ",
                    "     {$this->leaves[1]}  {$this->leaves[2]}{$this->leaves[0]}{$this->leaves[2]}  {$this->leaves[1]}     ",
                    "       {$this->leaves[0]}/{$this->trunk[1]}\\{$this->leaves[0]}       ",
                    "        {$this->trunk[1]} {$this->trunk[1]}        ",
                    "      {$this->branches[1]}{$this->trunk[1]}{$this->trunk[1]}{$this->trunk[1]}{$this->branches[3]}      ",
                    "     {$this->branches[0]}{$this->trunk[1]}{$this->trunk[1]} {$this->trunk[1]}{$this->trunk[1]}{$this->branches[2]}     ",
                    "    =============    "
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