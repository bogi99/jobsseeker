<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaticPagesKeywordsTest extends TestCase
{
    public function test_privacy_page_includes_default_keyword(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertSee('meta name="keywords"', false);
        $response->assertSee('Jobs');
    }

    public function test_terms_page_includes_default_keyword(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSee('meta name="keywords"', false);
        $response->assertSee('Jobrat');
    }

    public function test_about_page_includes_default_keyword(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('meta name="keywords"', false);
        $response->assertSee('JobRat');
    }
}
