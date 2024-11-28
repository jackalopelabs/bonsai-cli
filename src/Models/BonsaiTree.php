<?php

namespace Jackalopelabs\BonsaiCli\Models;

use Illuminate\Support\Carbon;

class BonsaiTree
{
    public $canvas;
    public $age;
    public $style;
    public $season;
    public $seed;
    public $created_at;
    public $updated_at;

    public function __construct(array $attributes)
    {
        $this->canvas = $attributes['canvas'] ?? [];
        $this->age = $attributes['age'] ?? 'young';
        $this->style = $attributes['style'] ?? 'formal';
        $this->season = $attributes['season'] ?? 'spring';
        $this->seed = $attributes['seed'] ?? null;
        
        $this->created_at = isset($attributes['created_at']) 
            ? Carbon::parse($attributes['created_at'])
            : Carbon::now();
            
        $this->updated_at = isset($attributes['updated_at'])
            ? Carbon::parse($attributes['updated_at'])
            : Carbon::now();
    }

    public function render(): string
    {
        $output = "\n";
        
        foreach ($this->canvas as $row) {
            $output .= implode('', $row) . "\n";
        }
        
        return $output;
    }

    public function toArray(): array
    {
        return [
            'canvas' => $this->canvas,
            'age' => $this->age,
            'style' => $this->style,
            'season' => $this->season,
            'seed' => $this->seed,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => Carbon::now()->toISOString(),
        ];
    }

    public function compress(): string
    {
        return gzcompress(serialize($this->toArray()));
    }

    public static function decompress(string $data): self
    {
        return new self(unserialize(gzuncompress($data)));
    }
} 