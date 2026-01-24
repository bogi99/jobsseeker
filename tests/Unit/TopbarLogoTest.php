<?php

namespace Tests\Unit;

use App\Livewire\TopbarLogo;
use Livewire\Livewire;
use Tests\TestCase;

class TopbarLogoTest extends TestCase
{
    public function test_initial_logo_is_one_of_expected(): void
    {
        $component = Livewire::test(TopbarLogo::class);

        $this->assertContains($component->get('logo'), ['logo.png', 'logo2.png']);
    }

    public function test_refresh_logo_keeps_it_in_allowed_set(): void
    {
        $component = Livewire::test(TopbarLogo::class);

        $component->call('refreshLogo');

        $this->assertContains($component->get('logo'), ['logo.png', 'logo2.png']);
    }
}
