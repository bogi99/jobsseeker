<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'stripe_session_id' => null,
            'payment_intent_id' => null,
            'amount' => 5000,
            'currency' => 'cad',
            'status' => Transaction::STATUS_PENDING,
            'metadata' => [],
            'expires_at' => null,
        ];
    }
}
