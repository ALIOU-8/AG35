<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param  bool  $fullWidth  Pages plein écran (messagerie) : pas de conteneur centré.
     */
    public function __construct(public ?string $title = null, public bool $fullWidth = false) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
