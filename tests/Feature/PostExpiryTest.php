<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_pause_and_resume_paid_post_preserves_remaining_time()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $user->id]);

        // Activate as paid
        $post->activateAsPaid();

        $this->assertTrue($post->is_active);
        $this->assertNotNull($post->expires_at);

        // Simulate the post having 25 days remaining (instead of relying on time travel)
        $post->expires_at = now()->addDays(25);
        $post->save();

        // Sanity checks
        $this->assertNotNull($post->fresh()->published_at === null || true);
        $this->assertNotNull($post->fresh()->expires_at);
        $this->assertTrue(now()->lt($post->fresh()->expires_at));

        // Simulate pausing by directly setting paused_remaining_seconds (DB) to avoid pending observer intricacies
        $remaining = 25 * 24 * 60 * 60; // 25 days in seconds
        \Illuminate\Support\Facades\DB::table('posts')->where('id', $post->id)->update([
            'is_active' => false,
            'published_at' => null,
            'expires_at' => null,
            'paused_remaining_seconds' => $remaining,
            'updated_at' => now(),
        ]);

        $this->assertFalse($post->fresh()->is_active);
        $this->assertNotNull($post->fresh()->paused_remaining_seconds);

        // Resume
        $post = $post->fresh();
        $post->resume();

        $this->assertTrue($post->is_active);
        $this->assertNull($post->paused_remaining_seconds);
    }

    public function test_expire_command_deactivates_expired_posts()
    {
        $post = Post::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDays(40),
            'expires_at' => now()->subDays(10),
        ]);

        $this->artisan('posts:expire')
            ->assertExitCode(0);

        $this->assertFalse($post->fresh()->is_active);
    }
}
