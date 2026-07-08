<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LazyImage extends Component
{
    public string $src;
    public string $alt;
    public ?string $class;
    public ?string $width;
    public ?string $height;

    public function __construct(
        string $src,
        string $alt = '',
        ?string $class = null,
        ?string $width = null,
        ?string $height = null
    ) {
        $this->src = $src;
        $this->alt = $alt;
        $this->class = $class;
        $this->width = $width;
        $this->height = $height;
    }

    public function render()
    {
        return view('components.lazy-image');
    }
}
