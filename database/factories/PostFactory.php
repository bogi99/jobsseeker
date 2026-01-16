<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraph(),
            'full_content' => null,
            'is_active' => false,
            'is_paid' => false,
            'is_featured' => false,
            'is_free' => false,
            'payment_status' => 'pending',
            'payment_intent_id' => null,
            'company_name' => $this->faker->company(),
        ];
    }
}
