<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\StripeClient;

class StripeWebhookController
{
    public function handle(Request $request): Response
    {
        // Log basic request info to make debugging easier during webhook setup.
        \Illuminate\Support\Facades\Log::info('Stripe webhook received', ['method' => $request->method(), 'path' => $request->path(), 'ip' => $request->ip()]);

        $payload = $request->getContent();

        // In tests, skip signature verification for simplicity. In prod, verify using the webhook secret.
        if (app()->environment('testing') || empty(config('services.stripe.webhook_secret'))) {
            $event = json_decode($payload);
        } else {
            $sig = $request->header('Stripe-Signature');

            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sig, config('services.stripe.webhook_secret'));
            } catch (\UnexpectedValueException $e) {
                // Invalid payload
                return response('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                // Invalid signature
                return response('Invalid signature', 400);
            }
        }

        if (! $event || ! isset($event->type)) {
            return response('Ignored', 200);
        }

        // Log the event type and a small subset of the payload for debugging.
        $eventId = $event->id ?? null;
        $eventType = $event->type ?? null;
        \Illuminate\Support\Facades\Log::info('Stripe event received', ['type' => $eventType, 'id' => $eventId]);

        // Resolve Stripe client: prefer a bound client (tests can bind a mock).
        // Otherwise construct using the configured secret only if it is present and valid.
        $client = null;

        if (app()->bound(StripeClient::class)) {
            $client = app()->make(StripeClient::class);
        } else {
            $secret = config('services.stripe.secret');

            if (is_string($secret) && str($secret)->trim()->isNotEmpty()) {
                // Pass API key explicitly using the array form to avoid constructor errors.
                $client = new StripeClient(['api_key' => $secret]);
            } elseif (is_array($secret)) {
                // If someone put the full config array in the config, allow it.
                $client = new StripeClient($secret);
            } else {
                \Illuminate\Support\Facades\Log::warning('Stripe API key not configured; skipping Stripe API retrieval fallbacks');
                $client = null;
            }
        }

        // Helper to extract metadata/post id from a known object (session or payment_intent)
        $extractMetadata = function ($object) {
            $metadata = $object->metadata ?? null;

            if (is_object($metadata)) {
                return [$metadata->post_id ?? null, $metadata->boost ?? 0];
            }

            if (is_array($metadata)) {
                return [$metadata['post_id'] ?? null, $metadata['boost'] ?? 0];
            }

            return [null, 0];
        };

        $postId = null;
        $boost = 0;
        $transaction = null; // resolved transaction if any

        if ($event->type === 'checkout.session.completed' || str_starts_with($event->type, 'checkout.session.')) {
            $object = $event->data->object;

            [$postId, $boost] = $extractMetadata($object);

            if (! $postId) {
                \Illuminate\Support\Facades\Log::warning('Checkout session missing metadata.post_id', ['session' => $object->id ?? null]);

                // Attempt to fetch the full session from Stripe by ID (if present) to
                // recover metadata that may be missing from the delivered event.
                $sessionId = $object->id ?? null;

                if ($sessionId) {
                    if ($client) {
                        try {
                            $retrieved = $client->checkout->sessions->retrieve($sessionId);

                            \Illuminate\Support\Facades\Log::info('Checkout session retrieved from Stripe API', ['session' => $sessionId, 'metadata' => $retrieved->metadata ?? null]);

                            [$postId, $boost] = $extractMetadata($retrieved);

                            // If the session didn't include post metadata, try to find a pending transaction by session id
                            if (! $postId && ! empty($retrieved->id)) {
                                $tx = \App\Models\Transaction::where('stripe_session_id', $retrieved->id)->where('status', \App\Models\Transaction::STATUS_PENDING)->first();

                                if ($tx) {
                                    $postId = $tx->post_id;
                                    $boost = (bool) ($tx->metadata['boost'] ?? 0);
                                    $transaction = $tx; // keep reference for later activation
                                }
                            }

                            if (! $postId && ! empty($retrieved->payment_intent)) {
                                // As a last resort, list sessions associated with the payment_intent
                                // and check their metadata too.
                                $pi = $retrieved->payment_intent;

                                try {
                                    $sessions = $client->checkout->sessions->all(['payment_intent' => $pi, 'limit' => 3]);

                                    if (! empty($sessions->data)) {
                                        foreach ($sessions->data as $s) {
                                            [$postId, $boost] = $extractMetadata($s);

                                            if ($postId) {
                                                break;
                                            }
                                        }

                                        if (! $postId) {
                                            \Illuminate\Support\Facades\Log::warning('No metadata found on sessions for payment_intent', ['payment_intent' => $pi, 'checked_sessions' => count($sessions->data)]);

                                            // As an additional fallback, try to find a pending transaction associated
                                            // with this payment_intent. Hosted links / other flows may not set metadata.
                                            $transaction = \App\Models\Transaction::where('payment_intent_id', $pi)->where('status', \App\Models\Transaction::STATUS_PENDING)->first();

                                            if ($transaction) {
                                                $postId = $transaction->post_id;
                                                $boost = (bool) ($transaction->metadata['boost'] ?? 0);
                                                $transaction = $transaction; // keep reference for later activation
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Failed to list checkout sessions for payment intent (fallback)', ['payment_intent' => $pi, 'error' => $e->getMessage()]);
                                }
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to retrieve checkout session from Stripe', ['session' => $sessionId, 'error' => $e->getMessage()]);
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Cannot retrieve checkout session from Stripe because client is not configured', ['session' => $sessionId]);
                    }
                }
            }
        }

        if (($event->type === 'payment_intent.succeeded' || str_starts_with($event->type, 'payment_intent.')) && ! $postId) {
            // Payment intent events sometimes don't include session metadata. Try to find the Checkout Session(s) for this PaymentIntent.
            $pi = $event->data->object;
            $piId = $pi->id ?? null;

            if ($piId) {
                // If we have a client, try to list sessions for the PI; otherwise only check DB fallback.
                if ($client) {
                    try {
                        $sessions = $client->checkout->sessions->all(['payment_intent' => $piId, 'limit' => 1]);

                        if (! empty($sessions->data) && isset($sessions->data[0])) {
                            $session = $sessions->data[0];
                            [$postId, $boost] = $extractMetadata($session);

                            if (! $postId) {
                                \Illuminate\Support\Facades\Log::warning('Checkout session found for payment intent but missing metadata', ['payment_intent' => $piId, 'session' => $session->id ?? null]);
                            }
                        } else {
                            // As a fallback, try to find a post that has the payment_intent_id set to this payment intent
                            $post = Post::where('payment_intent_id', $piId)->first();

                            if ($post) {
                                $postId = $post->id;
                                $boost = (bool) $post->is_featured;
                            }

                            // If we still couldn't resolve a post, try to find a pending transaction tied to this payment intent.
                            if (! $postId) {
                                $transaction = \App\Models\Transaction::where('payment_intent_id', $piId)->where('status', \App\Models\Transaction::STATUS_PENDING)->first();

                                if ($transaction) {
                                    $postId = $transaction->post_id;
                                    $boost = (bool) ($transaction->metadata['boost'] ?? 0);
                                    $transaction = $transaction; // keep reference for later activation
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to list checkout sessions for payment intent', ['payment_intent' => $piId, 'error' => $e->getMessage()]);
                    }
                } else {
                    // No client available; fall back to DB only.
                    $post = Post::where('payment_intent_id', $piId)->first();

                    if ($post) {
                        $postId = $post->id;
                        $boost = (bool) $post->is_featured;
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Stripe client not configured; cannot lookup sessions for payment_intent', ['payment_intent' => $piId]);
                    }
                }
            }
        }

        // Final fallback: if we still don't have a postId, and we couldn't match by session/payment_intent,
        // try to find exactly one pending transaction that has no Stripe identifiers and hasn't expired.
        // This corresponds to the 'hosted link' flow where we create a server-side transaction with
        // a post_id/user_id but no session_id/payment_intent. If there's exactly one such pending
        // transaction, use it to resolve the post id. If there are multiple, log and abort to avoid
        // accidentally activating the wrong post.
        if (! $postId && ! $transaction) {
            try {
                $candidates = \App\Models\Transaction::where('status', \App\Models\Transaction::STATUS_PENDING)
                    ->whereNull('stripe_session_id')
                    ->whereNull('payment_intent_id')
                    ->where('expires_at', '>=', now())
                    ->get();

                if ($candidates->count() === 1) {
                    $tx = $candidates->first();
                    $transaction = $tx;
                    $postId = $tx->post_id;
                    $boost = (bool) ($tx->metadata['boost'] ?? 0);

                    \Illuminate\Support\Facades\Log::info('Resolved post via single pending transaction fallback', ['transaction_id' => $tx->id, 'post_id' => $postId]);
                } elseif ($candidates->count() > 1) {
                    \Illuminate\Support\Facades\Log::warning('Multiple pending transactions found during webhook fallback; aborting auto-activation', ['count' => $candidates->count()]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error while attempting pending transaction fallback', ['error' => $e->getMessage()]);
            }
        }

        if ($postId) {
            $post = Post::find((int) $postId);

            if (! $post) {
                \Illuminate\Support\Facades\Log::warning('Post referenced by webhook not found', ['post_id' => $postId]);

                return response('OK', 200);
            }

            try {
                if ($post->payment_status !== 'paid') {
                    $post->activateAsPaid((bool) $boost);
                    \Illuminate\Support\Facades\Log::info('Post activated from Stripe webhook', ['post_id' => $postId, 'boost' => (bool) $boost]);

                    // If we resolved a transaction while reconciling, mark it completed as well.
                    if (! empty($transaction) && $transaction instanceof \App\Models\Transaction) {
                        try {
                            $transaction->markCompleted($event->data->object->payment_intent ?? null, $event->data->object->id ?? null);
                            \Illuminate\Support\Facades\Log::info('Transaction marked completed from webhook', ['transaction_id' => $transaction->id, 'post_id' => $postId]);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to mark transaction completed', ['transaction_id' => $transaction->id ?? null, 'error' => $e->getMessage()]);
                        }
                    }
                } else {
                    \Illuminate\Support\Facades\Log::info('Post already marked paid, skipping activation', ['post_id' => $postId]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to activate post from webhook', ['post_id' => $postId, 'error' => $e->getMessage()]);
            }
        } else {
            \Illuminate\Support\Facades\Log::warning('Unable to resolve post id from Stripe event', ['event_type' => $eventType, 'event_id' => $eventId]);
        }

        return response('OK', 200);
    }
}
