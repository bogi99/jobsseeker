<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\GeneratesMetaKeywords;
use Tests\TestCase;

class Dummy
{
    use GeneratesMetaKeywords;
}

class GeneratesMetaKeywordsTest extends TestCase
{
    public function test_generate_meta_keywords_with_posts_and_limit(): void
    {
        $dummy = new Dummy;

        $posts = collect([
            (object) ['tags' => collect([(object) ['name' => 'Remote'], (object) ['name' => 'Senior']])],
            (object) ['tags' => collect([(object) ['name' => 'Remote'], (object) ['name' => 'Full-time']])],
        ]);

        $meta = $dummy->generateMetaKeywords($posts, ['Jobs', 'coding'], 2);

        // Should contain defaults and up to 2 tag names
        $this->assertStringContainsString('Jobs', $meta);
        $this->assertStringContainsString('coding', $meta);
        $this->assertTrue(
            preg_match('/Remote|Senior|Full-time/', $meta) === 1
        );
    }
}
