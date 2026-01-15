<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class ExpirePosts extends Command
{
    protected $signature = 'posts:expire';

    protected $description = 'Expire posts that have passed their expiry date';

    public function handle(): int
    {
        $expired = Post::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        $this->info("Expired posts: {$expired}");

        return 0;
    }
}
