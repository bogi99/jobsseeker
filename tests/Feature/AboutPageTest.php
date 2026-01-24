<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    /**
     * About page is accessible.
     */
    public function test_about_page_returns_200_and_displays_heading(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200)
            ->assertSee('About');
    }
}
