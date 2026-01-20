<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class PostPaymentController
{
    public function success(Request $request, ?StripeClient $stripe = null)
    {
        $sessionId = $request->query('session_id');
        $postId = $request->query('post_id');

        // If Stripe provided a session_id, fetch the session and use its metadata to locate the post
        if ($sessionId) {
            try {
                $session = $stripe->checkout->sessions->retrieve($sessionId);
            } catch (\Exception $e) {
                // If we can't retrieve the session, show a generic page
                return redirect()->route('jobs.index')->with('error', 'Unable to verify payment session. If your payment was processed, it will be applied shortly.');
            }

            $metadata = $session->metadata ?? [];

            $postId = is_array($metadata) ? ($metadata['post_id'] ?? null) : ($metadata->post_id ?? null);
            $boost = is_array($metadata) ? ($metadata['boost'] ?? 0) : ($metadata->boost ?? 0);

            if ($postId) {
                $post = Post::find((int) $postId);

                if ($post && $post->payment_status !== 'paid') {
                    // Persist payment reference and activate immediately for a better UX
                    $post->update(['payment_intent_id' => $session->payment_intent ?? $session->id]);
                    $post->activateAsPaid((bool) $boost);
                }

                return redirect()->route('filament.customer.resources.posts.edit', ['record' => $postId])->with('success', 'Payment received — your post is now live.');
            }

            // If no post metadata, fall back to a generic success page
            return redirect()->route('jobs.index')->with('success', 'Payment successful. If your post doesn\'t appear as live yet, it will be activated shortly.');
        }

        // Fallback: if we have a post_id in the query (e.g., appended when creating the hosted buy link)
        if ($postId) {
            $post = Post::find((int) $postId);

            if ($post) {
                // Try to reconcile by checking Stripe session/payment intent if a session was provided and a client is available.
                $session = null;

                if ($sessionId && $stripe) {
                    try {
                        $session = $stripe->checkout->sessions->retrieve($sessionId);
                    } catch (\Exception $e) {
                        // Ignore retrieval failure and fall back to user-facing message.
                        $session = null;
                    }
                }

                // If we have a session and it reflects a successful payment, activate immediately.
                if ($session && ($session->payment_status === 'paid' || $session->status === 'complete')) {
                    $boost = (int) ($session->metadata->boost ?? $boost ?? 0);
                    $post->update(['payment_intent_id' => $session->payment_intent ?? $session->id]);
                    $post->activateAsPaid((bool) $boost);

                    return redirect()->route('filament.customer.resources.posts.edit', ['record' => $postId])->with('success', 'Payment received — your post is now live.');
                }

                // If no Stripe client is configured, we can't verify payment server-side. Log this so
                // an admin can reconcile; do not auto-activate for safety.
                if (! $stripe) {
                    \Illuminate\Support\Facades\Log::warning('Post payment success page hit with post_id but no Stripe client configured; manual reconciliation may be needed', ['post_id' => $postId]);

                    return redirect()->route('filament.customer.resources.posts.edit', ['record' => $postId])->with('success', 'Payment recorded — if your post is not live yet, it will be activated shortly after verification.');
                }

                // Default page
                return redirect()->route('jobs.index')->with('success', 'Payment successful.');
            }
        }
    }
}
