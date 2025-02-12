<?php

namespace App\View\Components\Bonsai;

use Illuminate\View\Component;

class DarkModeToggle extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $class = '',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('bonsai.components.dark-mode-toggle');
    }
} 