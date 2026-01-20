<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class ReconcileStripePayments extends Command
{
    protected $signature = 'stripe:reconcile-payments {--dry : Do not persist changes, just report}';

    protected $description = 'Verify Stripe payments for pending posts and activate posts that were paid.';

    public function handle(StripeClient $stripe): int
    {
        $this->info('Starting Stripe payment reconciliation');

        $posts = Post::query()
            ->where('payment_status', '!=', 'paid')
            ->whereNotNull('payment_intent_id')
            ->get();

        $this->info('Found '.$posts->count().' posts to reconcile');

        // Also check pending transactions which may not have populated the post.payment_intent_id yet.
        $transactions = \App\Models\Transaction::where('status', \App\Models\Transaction::STATUS_PENDING)->get();

        $this->info('Found '.$transactions->count().' pending transactions to reconcile');

        $fixed = 0;
        $errors = [];

        foreach ($transactions as $tx) {
            $this->line("Checking transaction {$tx->id} (post: {$tx->post_id}, pi: {$tx->payment_intent_id}, session: {$tx->stripe_session_id})");

            $paid = false;
            $boost = (bool) ($tx->metadata['boost'] ?? 0);

            // Prefer payment_intent, then session
            if ($tx->payment_intent_id) {
                try {
                    $pi = $stripe->paymentIntents->retrieve($tx->payment_intent_id);

                    if ($pi && ($pi->status === 'succeeded' || ($pi->amount_received ?? 0) > 0)) {
                        $paid = true;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Transaction {$tx->id}: failed to retrieve payment_intent {$tx->payment_intent_id}: {$e->getMessage()}";

                    continue;
                }
            } elseif ($tx->stripe_session_id) {
                try {
                    $session = $stripe->checkout->sessions->retrieve($tx->stripe_session_id);

                    if ($session && ($session->payment_status === 'paid' || $session->status === 'complete')) {
                        $paid = true;
                        $boost = isset($session->metadata['boost']) ? (bool) $session->metadata['boost'] : $boost;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Transaction {$tx->id}: failed to retrieve session {$tx->stripe_session_id}: {$e->getMessage()}";

                    continue;
                }
            } else {
                $this->line("Transaction {$tx->id} has no stripe identifiers; skipping (requires manual reconciliation)");
            }

            if ($paid) {
                $this->line("Transaction {$tx->id} appears paid. Activating post {$tx->post_id}...");

                if ($this->option('dry')) {
                    $this->line("--dry run: would activate post {$tx->post_id} for transaction {$tx->id}");
                } else {
                    try {
                        $post = Post::find($tx->post_id);

                        if ($post && $post->payment_status !== 'paid') {
                            $post->activateAsPaid($boost);
                            $tx->markCompleted($tx->payment_intent_id ?? null, $tx->stripe_session_id ?? null);
                            $fixed++;
                            \Illuminate\Support\Facades\Log::info('Reconciled and activated post via transaction', ['transaction_id' => $tx->id, 'post_id' => $post->id]);
                        }
                    } catch (\Throwable $e) {
                        $errors[] = "Transaction {$tx->id}: activation failed: {$e->getMessage()}";
                    }
                }
            } else {
                $this->line("Transaction {$tx->id} not paid yet (payment status not confirmed by Stripe)");
            }
        }

        foreach ($posts as $post) {
            $this->line("Checking post {$post->id} (payment reference: {$post->payment_intent_id})");

            $ref = $post->payment_intent_id;

            $paid = false;
            $boost = (bool) $post->is_featured;

            try {
                if ($ref) {
                    // PaymentIntent IDs start with pi_ and are authoritative.
                    if (str_starts_with($ref, 'pi_')) {
                        $pi = $stripe->paymentIntents->retrieve($ref);

                        if ($pi && ($pi->status === 'succeeded' || ($pi->amount_received ?? 0) > 0)) {
                            $paid = true;
                        }
                    } else {
                        // Treat as a session id fallback
                        $session = $stripe->checkout->sessions->retrieve($ref);

                        if ($session && ($session->payment_status === 'paid' || ($session->status === 'complete'))) {
                            $paid = true;
                            $boost = isset($session->metadata['boost']) ? (bool) $session->metadata['boost'] : $boost;
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Post {$post->id}: failed to retrieve reference {$ref}: {$e->getMessage()}";

                continue;
            }

            if ($paid) {
                $this->line("Post {$post->id} appears paid. Activating...");

                if ($this->option('dry')) {
                    $this->line("--dry run: would activate post {$post->id}");
                } else {
                    try {
                        if ($post->payment_status !== 'paid') {
                            $post->activateAsPaid($boost);
                            $fixed++;
                            \Illuminate\Support\Facades\Log::info('Reconciled and activated post', ['post_id' => $post->id]);
                        }
                    } catch (\Throwable $e) {
                        $errors[] = "Post {$post->id}: activation failed: {$e->getMessage()}";
                    }
                }
            } else {
                $this->line("Post {$post->id} not paid yet (payment status not confirmed by Stripe)");
            }
        }

        $this->info("Reconciliation complete. Fixed: {$fixed}. Errors: ".count($errors));

        if (! empty($errors)) {
            $this->warn("Errors:\n".implode("\n", $errors));
        }

        return 0;
    }
}
