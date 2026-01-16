<?php

namespace Tests\Feature;

use App\Filament\Customer\Resources\PostResource\Pages\CreatePost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FreePostCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_can_create_free_post()
    {
        $user = User::factory()->create(['is_free' => true]);

        $this->actingAs($user);

        // Visiting the free route should redirect to the Customer panel create page
        $response = $this->get(route('customer.posts.create.free'));
        $response->assertRedirect(route('filament.customer.resources.posts.create'));

        // Follow the redirect so the flashed session key is available to the Livewire form.
        $this->get(route('filament.customer.resources.posts.create'));

        // Then creating the post should work as a free post.
        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Free job title',
                'content' => 'Short content',
                'company_name' => 'Example Co',
                'user_id' => $user->id,
                'is_free' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'title' => 'Free job title',
            'user_id' => $user->id,
            'is_free' => true,
            'is_paid' => false,
        ]);
    }

    public function test_non_free_user_cannot_create_free_post()
    {
        $user = User::factory()->create(['is_free' => false]);

        $this->actingAs($user);

        // Visiting the free route is forbidden for non-free users
        $this->get(route('customer.posts.create.free'))
            ->assertStatus(403);

        // Attempting to create a post with `is_free` flagged directly on the regular create
        // flow should still succeed but the server must strip the `is_free` flag.
        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Attempted free job',
                'content' => 'Short content',
                'company_name' => 'Example Co',
                'user_id' => $user->id,
                'is_free' => true, // malicious attempt
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'title' => 'Attempted free job',
            'is_free' => false,
        ]);
    }

    /**
     * Get the value of user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
