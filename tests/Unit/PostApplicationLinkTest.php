<?php

namespace Tests\Unit;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApplicationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailto_values_are_saved_with_double_slash(): void
    {
        $p1 = Post::factory()->create(['application_link' => 'mailto://hay@samkhangyi.com']);
        $this->assertSame('mailto://hay@samkhangyi.com', $p1->fresh()->application_link);

        $p2 = Post::factory()->create(['application_link' => 'mailto:foo@bar.com']);
        $this->assertSame('mailto://foo@bar.com', $p2->fresh()->application_link);

        $p3 = Post::factory()->create(['application_link' => 'baz@domain.test']);
        $this->assertSame('mailto://baz@domain.test', $p3->fresh()->application_link);
    }

    public function test_plain_hostname_gets_https_prepended(): void
    {
        $post = Post::factory()->create([
            'application_link' => 'example.com',
        ]);

        $this->assertSame('https://example.com', $post->fresh()->application_link);
    }

    public function test_other_schemes_are_preserved(): void
    {
        $post = Post::factory()->create([
            'application_link' => 'https://acme.example/jobs/apply',
        ]);

        $this->assertSame('https://acme.example/jobs/apply', $post->fresh()->application_link);
    }
}
