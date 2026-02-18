<?php

namespace Tests\Unit;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostLogoDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_logo_is_deleted_when_post_deleted(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('company-logos/to-delete.png', 'contents');

        $post = Post::factory()->create(['company_logo' => 'company-logos/to-delete.png']);

        $this->assertTrue(Storage::disk('public')->exists('company-logos/to-delete.png'));

        $post->delete();

        $this->assertFalse(Storage::disk('public')->exists('company-logos/to-delete.png'));
    }

    public function test_external_logo_is_not_deleted_on_post_delete(): void
    {
        Storage::fake('public');

        $post = Post::factory()->create(['company_logo' => 'https://cdn.example/logo.png']);

        // nothing to delete from the disk — no exceptions should be thrown
        $post->delete();

        $this->assertTrue(true);
    }
}
