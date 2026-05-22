<?php

namespace Tests\Feature;

use App\Filament\Customer\Resources\PostResource\Pages\CreatePost;
use App\Filament\Customer\Resources\PostResource\Pages\EditPost;
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

        // Also verify the new /customer/create path redirects to the same place
        $response = $this->get('/customer/create');
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

        // Also ensure the new /customer/create path is forbidden for non-free users
        $this->get('/customer/create')->assertStatus(403);

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

    public function test_free_user_can_save_existing_free_post_without_redirect_error()
    {
        $user = User::factory()->create(['is_free' => true]);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original free job title',
            'content' => 'Original short content',
            'is_free' => true,
            'is_paid' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->fillForm([
                'title' => 'Updated free job title',
                'content' => '<p>Updated short content</p>',
                'company_name' => 'Example Co',
                'user_id' => $user->id,
                'is_free' => true,
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('filament.customer.resources.posts.index'));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated free job title',
            'content' => '<p>Updated short content</p>',
            'is_free' => true,
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
