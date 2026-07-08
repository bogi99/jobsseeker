<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSetPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sets_a_users_password_by_id(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'password' => Hash::make('old-password'),
        ]);

        $this->artisan('app:user-set-password', [
            'user' => (string) $user->id,
            'password' => 'new-password',
        ])
            ->expectsOutput("Password for '{$user->id}' updated successfully.")
            ->assertSuccessful();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }

    public function test_it_sets_a_users_password_by_email(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'password' => Hash::make('old-password'),
        ]);

        $this->artisan('app:user-set-password', [
            'user' => $user->email,
            'password' => 'fresh-password',
        ])
            ->expectsOutput("Password for '{$user->email}' updated successfully.")
            ->assertSuccessful();

        $user->refresh();

        $this->assertTrue(Hash::check('fresh-password', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }

    public function test_it_fails_when_the_user_cannot_be_found(): void
    {
        $this->artisan('app:user-set-password', [
            'user' => 'missing@example.com',
            'password' => 'new-password',
        ])
            ->expectsOutput('User missing@example.com not found.')
            ->assertFailed();
    }
}
