<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    public $title;
    public $subtitle;

    public function __construct($title = null, $subtitle = null)
    {
        $this->title = $title ?? null;
        $this->subtitle = $subtitle ?? null;
    }

    public function render()
    {
        return view('components.card');
    }
}
