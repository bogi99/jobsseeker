<?php

namespace Tests\Unit;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostCompanyLogoUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_no_company_logo(): void
    {
        $post = Post::factory()->create(['company_logo' => null]);

        $this->assertNull($post->company_logo_url);
    }

    public function test_returns_external_url_unchanged(): void
    {
        $url = 'https://cdn.example.com/logo.png';

        $post = Post::factory()->create(['company_logo' => $url]);

        $this->assertSame($url, $post->fresh()->company_logo_url);
    }

    public function test_returns_data_uri_unchanged(): void
    {
        $data = 'data:image/svg+xml;utf8,<svg></svg>';

        $post = Post::factory()->create(['company_logo' => $data]);

        $this->assertSame($data, $post->fresh()->company_logo_url);
    }

    public function test_returns_storage_url_when_file_exists_on_public_disk(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('company-logos/test.png', 'contents');

        $post = Post::factory()->create(['company_logo' => 'company-logos/test.png']);

        $this->assertSame(Storage::disk('public')->url('company-logos/test.png'), $post->fresh()->company_logo_url);
    }

    public function test_returns_placeholder_when_file_missing(): void
    {
        $post = Post::factory()->create(['company_logo' => 'does/not/exist.png']);

        $this->assertSame(asset('images/company-placeholder.svg'), $post->fresh()->company_logo_url);
    }
}
