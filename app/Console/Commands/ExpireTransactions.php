<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireTransactions extends Command
{
    protected $signature = 'transactions:expire {--dry : Do not persist changes, just report}';

    protected $description = 'Expire stale pending transactions and mark them cancelled.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        $now = now();

        $transactions = Transaction::where('status', Transaction::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->get();

        $this->info('Found ' . $transactions->count() . ' expired pending transactions');

        if ($transactions->isEmpty()) {
            return 0;
        }

        foreach ($transactions as $tx) {
            $this->line("Expiring transaction {$tx->id} (user: {$tx->user_id}, post: {$tx->post_id})");

            if ($dry) {
                continue;
            }

            try {
                $tx->update(['status' => Transaction::STATUS_CANCELLED]);

                Log::info('Expired transaction marked cancelled', ['transaction_id' => $tx->id, 'user_id' => $tx->user_id, 'post_id' => $tx->post_id]);
            } catch (\Throwable $e) {
                Log::error('Failed to expire transaction', ['transaction_id' => $tx->id, 'error' => $e->getMessage()]);
                $this->error("Failed to expire transaction {$tx->id}: {$e->getMessage()}");
            }
        }

        $this->info('Done.');

        return 0;
    }
}
