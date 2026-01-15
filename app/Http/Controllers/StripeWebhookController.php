<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController
{
    public function handle(Request $request): Response
    {
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

        if ($event->type === 'checkout.session.completed' || $event->type === 'payment_intent.succeeded') {
            $object = $event->data->object;

            // Metadata may be an object or array depending on how the SDK decodes it in tests
            $metadata = $object->metadata ?? null;

            $postId = null;
            $boost = 0;

            if (is_object($metadata)) {
                $postId = $metadata->post_id ?? null;
                $boost = $metadata->boost ?? 0;
            } elseif (is_array($metadata)) {
                $postId = $metadata['post_id'] ?? null;
                $boost = $metadata['boost'] ?? 0;
            }

            if ($postId) {
                $post = Post::find((int) $postId);

                if ($post && $post->payment_status !== 'paid') {
                    // Use the model's activation helper to correctly set expiry and paid flags.
                    $post->activateAsPaid((bool) $boost);
                }
            }
        }

        return response('OK', 200);
    }
}
