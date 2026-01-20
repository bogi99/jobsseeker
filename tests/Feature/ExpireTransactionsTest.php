<?php

namespace Tests\Feature;

use App\Filament\Customer\Resources\PostResource\Pages\CreatePost;
use App\Models\Post;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpireTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_transactions_are_cancelled_by_command()
    {
        $tx = Transaction::factory()->create([
            'status' => Transaction::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertEquals(Transaction::STATUS_PENDING, $tx->fresh()->status);

        $this->artisan('transactions:expire')
            ->assertExitCode(0);

        $this->assertEquals(Transaction::STATUS_CANCELLED, $tx->fresh()->status);
    }

    public function test_after_expiry_user_can_create_new_transaction()
    {
        /** @var User $user */
        $user = User::factory()->create(['is_free' => false]);

        $this->actingAs($user);

        // Create an expired pending transaction for this user
        $expired = Transaction::factory()->create([
            'user_id' => $user->id,
            'status' => Transaction::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        // Expire it
        $this->artisan('transactions:expire')
            ->assertExitCode(0);

        $this->assertEquals(Transaction::STATUS_CANCELLED, $expired->fresh()->status);

        // Bind a mock Stripe client so Create & Pay succeeds
        $mockSession = (object) ['id' => 'cs_new', 'url' => 'https://checkout.stripe/checkout', 'payment_intent' => 'pi_new'];

        $mockSessions = new class($mockSession) {
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

        $mockCheckout = new class($mockSessions) {
            public $sessions;
            public function __construct($sessions)
            {
                $this->sessions = $sessions;
            }
        };

        $mockClient = new class($mockCheckout) {
            public $checkout;
            public function __construct($checkout)
            {
                $this->checkout = $checkout;
            }
        };

        $this->app->instance(\Stripe\StripeClient::class, $mockClient);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'After expiry job',
                'content' => 'Content',
                'company_name' => 'Example Co',
                'user_id' => $user->id,
                'is_paid' => true,
                'is_featured' => false,
                'is_active' => true,
            ])
            ->call('createAndPay')
            ->assertRedirect('https://checkout.stripe/checkout');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'status' => Transaction::STATUS_PENDING,
        ]);
    }
}
