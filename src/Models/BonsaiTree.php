<?php

namespace Jackalopelabs\BonsaiCli\Models;

class BonsaiTree
{
    public $age;
    public $style;
    public $created_at;
    public $updated_at;

    public function __construct($age, $style)
    {
        $this->age = $age;
        $this->style = $style;
        $this->created_at = time();
        $this->updated_at = time();
    }

    public function render()
    {
        return "
    ^
   ^^^
  ^^^^^
 ^^^^^^^
   |||
   |||
  =====
";
    }
} 