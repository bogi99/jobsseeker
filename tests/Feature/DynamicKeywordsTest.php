<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicKeywordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_includes_tag_names_in_keywords_meta(): void
    {
        // Create a tag and attach it to all created posts to ensure the tag appears
        $tag = Tag::create(['name' => 'Remote', 'slug' => 'remote']);

        Post::factory()->count(10)->create(['is_active' => true])->each(function (Post $post) use ($tag) {
            $post->tags()->attach($tag->id);
        });

        $response = $this->get('/');

        $response->assertStatus(200);

        // Ensure meta keywords contains the tag name
        $response->assertSee('meta name="keywords"', false);
        $response->assertSee('Remote');
    }
}
