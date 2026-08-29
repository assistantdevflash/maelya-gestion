<?php

namespace App\View\Components\Emails;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class App extends Component
{
    public function __construct(
        public string $titre = 'Maëlya Gestion',
        public ?string $sousTitre = null,
    ) {}

    public function render(): View
    {
        return view('emails.layouts.app');
    }
}
