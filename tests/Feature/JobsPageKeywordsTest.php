<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobsPageKeywordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_index_uses_default_logo_when_post_has_no_company_logo(): void
    {
        Post::factory()->create([
            'is_active' => true,
            'company_logo' => null,
        ]);

        $response = $this->get('/jobs');

        $response->assertStatus(200);
        $response->assertSee(asset('images/jobrat-canada_150x150.png'));
    }

    public function test_jobs_index_includes_tag_names_in_keywords_meta(): void
    {
        $tag = Tag::create(['name' => 'Remote', 'slug' => 'remote']);

        Post::factory()->count(5)->create(['is_active' => true])->each(function (Post $post) use ($tag) {
            $post->tags()->attach($tag->id);
        });

        $response = $this->get('/jobs');

        $response->assertStatus(200);
        $response->assertSee('meta name="keywords"', false);
        $response->assertSee('Remote');
    }

    public function test_jobs_show_includes_post_tag_in_keywords_meta(): void
    {
        $tag = Tag::create(['name' => 'Backend', 'slug' => 'backend']);

        $post = Post::factory()->create(['is_active' => true]);
        $post->tags()->attach($tag->id);

        $response = $this->get('/jobs/'.$post->id);

        $response->assertStatus(200);
        $response->assertSee('meta name="keywords"', false);
        $response->assertSee('Backend');
    }

    public function test_jobs_show_uses_default_logo_when_post_has_no_company_logo(): void
    {
        $post = Post::factory()->create([
            'is_active' => true,
            'company_logo' => null,
        ]);

        $response = $this->get('/jobs/'.$post->id);

        $response->assertStatus(200);
        $response->assertSee(asset('images/jobrat-canada_150x150.png'));
    }
}
