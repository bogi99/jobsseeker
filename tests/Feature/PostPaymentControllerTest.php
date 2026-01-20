<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_with_post_id_and_no_stripe_client_redirects_to_edit()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_paid' => false,
            'is_active' => false,
            'payment_status' => 'pending',
        ]);

        // Ensure no StripeClient is bound in the container
        $this->app->offsetUnset(\Stripe\StripeClient::class);

        $response = $this->get(route('posts.payment.success', ['post_id' => $post->id]));

        $response->assertRedirect(route('filament.customer.resources.posts.edit', ['record' => $post->id]));
        $response->assertSessionHas('success');
    }
}
