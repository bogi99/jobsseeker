<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserSetPasswordCommand extends Command
{
    protected $signature = 'app:user-set-password {user} {password}';

    protected $description = 'Set a new password for a user by ID or email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userSelected = $this->argument('user');
        $newPassword = $this->argument('password');

        $user = User::query()
            ->where('id', $userSelected)
            ->orWhere('email', $userSelected)
            ->first();

        if (! $user) {
            $this->error('User '.$userSelected.' not found.');

            return Command::FAILURE;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->info('Password for \''.$userSelected.'\' updated successfully.');

        return Command::SUCCESS;
    }
}
