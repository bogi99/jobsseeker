<?php

namespace Tests\Feature;

use Tests\TestCase;

class TermsCustomKeywordsTest extends TestCase
{
    public function test_terms_page_includes_controller_default_keyword(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSee('meta name="keywords"', false);
        $response->assertSee('Jobrat');
    }
}
