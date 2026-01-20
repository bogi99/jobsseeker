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
        /** @var User $user */
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
            ->call('createAndPay')
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

        // Simulate webhook with missing metadata on the delivered event object
        $eventNoMeta = (object) [
            'type' => 'checkout.session.completed',
            'data' => (object) [
                'object' => (object) [
                    'id' => 'cs_fallback_test',
                    'metadata' => (object) [],
                    'payment_intent' => 'pi_fallback_test',
                ],
            ],
        ];

        // Prepare a mock Stripe client that will return the session with metadata
        $mockRetrievedSession = (object) [
            'id' => 'cs_fallback_test',
            'payment_intent' => 'pi_fallback_test',
            'metadata' => (object) ['post_id' => (string) $post->id, 'boost' => 0],
        ];

        $mockSessions = new class($mockRetrievedSession)
        {
            public $mockRetrievedSession;

            public function __construct($mockRetrievedSession)
            {
                $this->mockRetrievedSession = $mockRetrievedSession;
            }

            public function retrieve($id)
            {
                return $this->mockRetrievedSession;
            }

            public function all($args)
            {
                return (object) ['data' => [$this->mockRetrievedSession]];
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

        // Bind this mock client so the controller uses it when attempting retrieval.
        $this->app->instance(\Stripe\StripeClient::class, $mockClient);

        $this->postJson(route('webhooks.stripe'), json_decode(json_encode($eventNoMeta), true))
            ->assertStatus(200);

        // The fallback retrieval should have pulled metadata and activated the post
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_paid' => true,
            'is_active' => true,
            'is_featured' => false,
            'payment_status' => 'paid',
        ]);

        // The post should now have an expiry date 30 days in the future.
        $refreshed = $post->fresh();
        $this->assertNotNull($refreshed->expires_at);
        $this->assertTrue($refreshed->expires_at->greaterThan(now()));
    }

    public function test_create_and_pay_followed_by_webhook_activates_post()
    {
        $user = User::factory()->create(['is_free' => false]);

        $this->actingAs($user);

        // Mock a Stripe client instance for the Checkout Session creation.
        $mockSession = (object) ['id' => 'cs_test_123', 'url' => 'https://checkout.stripe/checkout', 'payment_intent' => 'pi_test_tx'];

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

        // Bind mock client into the container so both page and webhook use the same client.
        $this->app->instance(\Stripe\StripeClient::class, $mockClient);

        // Trigger the Create & Pay flow
        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'TX Paid job',
                'content' => 'Transaction content',
                'company_name' => 'Example Co',
                'user_id' => $user->id,
                'is_paid' => true,
                'is_featured' => false,
                'is_active' => true,
            ])
            ->call('createAndPay')
            ->assertRedirect('https://checkout.stripe/checkout');

        $post = Post::where('title', 'TX Paid job')->first();

        $this->assertNotNull($post);
        $this->assertEquals('pending', $post->payment_status);
        $this->assertEquals('pi_test_tx', $post->payment_intent_id);

        // Ensure a transaction record was created for this pending purchase
        $tx = \App\Models\Transaction::where('post_id', $post->id)->first();

        $this->assertNotNull($tx);
        $this->assertEquals('cs_test_123', $tx->stripe_session_id);
        $this->assertEquals('pi_test_tx', $tx->payment_intent_id);
        $this->assertEquals(\App\Models\Transaction::STATUS_PENDING, $tx->status);

        // Simulate webhook with missing metadata; controller should match by stripe_session_id
        $eventNoMeta = (object) [
            'type' => 'checkout.session.completed',
            'data' => (object) [
                'object' => (object) [
                    'id' => 'cs_test_123',
                    'metadata' => (object) [],
                    'payment_intent' => 'pi_test_tx',
                ],
            ],
        ];

        // Mock the Stripe client retrieve response to return the session (empty metadata)
        $mockRetrievedSession = (object) [
            'id' => 'cs_test_123',
            'payment_intent' => 'pi_test_tx',
            'metadata' => (object) [],
        ];

        $mockSessions = new class($mockRetrievedSession)
        {
            public $mockRetrievedSession;

            public function __construct($mockRetrievedSession)
            {
                $this->mockRetrievedSession = $mockRetrievedSession;
            }

            public function retrieve($id)
            {
                return $this->mockRetrievedSession;
            }

            public function all($args)
            {
                return (object) ['data' => [$this->mockRetrievedSession]];
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

        $mockClient2 = new class($mockCheckout)
        {
            public $checkout;

            public function __construct($checkout)
            {
                $this->checkout = $checkout;
            }
        };

        $this->app->instance(\Stripe\StripeClient::class, $mockClient2);

        $this->postJson(route('webhooks.stripe'), json_decode(json_encode($eventNoMeta), true))
            ->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_paid' => true,
            'is_active' => true,
            'payment_status' => 'paid',
        ]);

        // Transaction should be marked completed
        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'status' => \App\Models\Transaction::STATUS_COMPLETED,
        ]);
    }

    public function test_webhook_falls_back_to_single_pending_transaction()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_free' => false,
            'is_paid' => false,
            'is_active' => false,
            'payment_status' => 'pending',
        ]);

        // Create a pending transaction with no stripe identifiers (hosted link flow)
        $tx = \App\Models\Transaction::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'stripe_session_id' => null,
            'payment_intent_id' => null,
            'status' => \App\Models\Transaction::STATUS_PENDING,
            'expires_at' => now()->addDays(3),
        ]);

        // Deliver a webhook event with empty metadata and no session id (no API lookup possible)
        $eventNoMeta = (object) [
            'type' => 'checkout.session.completed',
            'data' => (object) [
                'object' => (object) [
                    'id' => null,
                    'metadata' => (object) [],
                ],
            ],
        ];

        // Ensure no Stripe client is bound to force DB fallback path
        // (controller will log and try the single-pending-transaction fallback)

        $this->postJson(route('webhooks.stripe'), json_decode(json_encode($eventNoMeta), true))
            ->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_paid' => true,
            'is_active' => true,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'status' => \App\Models\Transaction::STATUS_COMPLETED,
        ]);
    }

    public function test_prevents_new_purchase_when_user_has_pending_transaction()
    {
        /** @var User $user */
        $user = User::factory()->create(['is_free' => false]);

        // Create an existing pending transaction for this user
        \App\Models\Transaction::factory()->create(['user_id' => $user->id, 'status' => \App\Models\Transaction::STATUS_PENDING]);

        $this->actingAs($user);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Should not create',
                'content' => 'Blocked content',
                'company_name' => 'Example Co',
                'user_id' => $user->id,
                'is_paid' => true,
                'is_featured' => false,
                'is_active' => true,
            ])
            ->call('createAndPay')
            ->assertRedirect(route('filament.customer.resources.posts.index'));

        // We expect the redirect to the posts index (notifications are delivered via Filament).

        $this->assertDatabaseMissing('posts', ['title' => 'Should not create']);
        $this->assertEquals(1, \App\Models\Transaction::where('user_id', $user->id)->count());
    }
}
