<?php

namespace App\Livewire;

use Livewire\Component;

class TopbarLogo extends Component
{
    public string $logo;

    protected array $logos = ['logo.png', 'logo2.png'];

    public function mount(): void
    {
        $this->pickRandom();
    }

    protected function pickRandom(): void
    {
        $this->logo = $this->logos[array_rand($this->logos)];
    }

    public function refreshLogo(): void
    {
        $this->pickRandom();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.topbar-logo');
    }
}
