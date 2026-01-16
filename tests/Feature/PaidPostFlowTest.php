<?php

namespace Tests\Feature;

use App\Filament\Customer\Resources\PostResource\Pages\CreatePost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaidPostFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_create_redirects_to_checkout_and_post_pending()
    {
        $user = User::factory()->create(['is_free' => false]);

        $this->actingAs($user);

        // Mock a Stripe client instance so we don't hit the network. The CreatePost class
        // resolves StripeClient via the container with makeWith(), so swap the binding.
        $mockSession = (object) ['url' => 'https://checkout.stripe/checkout', 'payment_intent' => 'pi_test'];

        $mockSessions = new class($mockSession)
        {
            public $mockSession;

            public function __construct($mockSession)
            {
                $this->mockSession = $mockSession;
            }

            public function create($args)
            {
                return $this->mockSession;
            }
        };

        $mockCheckout = new class($mockSessions)
        {
            public $sessions;

            public function __construct($sessions)
            {
                $this->sessions = $sessions;
            }
        };

        $mockClient = new class($mockCheckout)
        {
            public $checkout;

            public function __construct($checkout)
            {
                $this->checkout = $checkout;
            }
        };
        $this->app->instance(\Stripe\StripeClient::class, $mockClient);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Paid job title',
                'content' => 'Paid content',
                'company_name' => 'Example Co',
                'user_id' => $user->id,
                'is_paid' => true,
                'is_featured' => false,
                'is_active' => true,
            ])
            ->call('create')
            ->assertRedirect('https://checkout.stripe/checkout');

        $this->assertDatabaseHas('posts', [
            'title' => 'Paid job title',
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'is_active' => false,
            'payment_intent_id' => 'pi_test',
        ]);
    }

    public function test_webhook_marks_post_paid_active_and_boosted()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_free' => false,
            'is_paid' => false,
            'is_active' => false,
            'payment_status' => 'pending',
        ]);

        $event = (object) [
            'type' => 'checkout.session.completed',
            'data' => (object) [
                'object' => (object) [
                    'metadata' => (object) ['post_id' => (string) $post->id, 'boost' => 1],
                    'payment_intent' => 'pi_test_webhook',
                ],
            ],
        ];

        $this->postJson(route('webhooks.stripe'), json_decode(json_encode($event), true))
            ->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_paid' => true,
            'is_active' => true,
            'is_featured' => true,
            'payment_status' => 'paid',
        ]);

        // The post should now have an expiry date 30 days in the future.
        $refreshed = $post->fresh();
        $this->assertNotNull($refreshed->expires_at);
        $this->assertTrue($refreshed->expires_at->greaterThan(now()));
    }
}
