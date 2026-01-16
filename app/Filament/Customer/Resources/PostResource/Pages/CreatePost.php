<?php

namespace App\Filament\Customer\Resources\PostResource\Pages;

use App\Filament\Customer\Resources\PostResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Stripe\StripeClient;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    // Flags used while creating so we can trigger external checkout after the DB record is created.
    protected bool $paymentRequested = false;

    protected bool $paymentBoostRequested = false;

    protected ?string $checkoutRedirectUrl = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        $data['user_id'] = $user?->id;

        // If the UI requested a free post via session OR the submitted payload asks for it,
        // ensure the current user is actually allowed to create free posts. Otherwise, deny.
        $formState = method_exists($this, 'form') ? $this->form->getState() : [];

        // Determine if this request is operating in the free flow. We prefer session flag set by
        // the `EnsureUserHasFreeAccess` middleware, falling back to submitted form state.
        $isRequestedFree = (bool) (session('customer_free_flow', false) || ($data['is_free'] ?? false) || ($formState['is_free'] ?? false));

        // Clear the session marker so it doesn't affect future unrelated requests.
        if ($isRequestedFree) {
            session()->forget('customer_free_flow');
        }

        if ($isRequestedFree && $user?->is_free) {
            // Allowed free flow for eligible users.
            $data['is_free'] = true;
            $data['is_paid'] = false;
        } else {
            // Enforce server-side invariant: only eligible users can have free posts.
            $data['is_free'] = false;

            // Handle the paid flow request. Do not trust `is_paid` from the client as proof of payment.
            $requestedPaid = (bool) ($data['is_paid'] ?? false) || (bool) ($formState['is_paid'] ?? false);

            if ($requestedPaid) {
                // Mark the DB record as pending payment and not active. We'll create a Checkout Session
                // in afterCreate and redirect the user to Stripe.
                $this->paymentRequested = true;
                $this->paymentBoostRequested = (bool) ($data['is_featured'] ?? false);

                $data['is_paid'] = false;
                $data['is_active'] = false;
                $data['payment_status'] = 'pending';
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->paymentRequested) {
            return;
        }

        $post = $this->getRecord();

        // Create a Stripe Checkout Session for the pending post. Use the container binding when
        // resolving the client so tests can swap the implementation.
        // Resolve the client from the container so tests can substitute a mock instance.
        $client = app()->make(StripeClient::class);

        $amount = config('services.stripe.price_post', 5000);
        $boostAmount = config('services.stripe.price_post_boost', 9000);

        $unitAmount = $this->paymentBoostRequested ? $boostAmount : $amount;

        $session = $client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'usd'),
                    'product_data' => ['name' => $this->paymentBoostRequested ? 'Posting + Boost' : 'Posting'],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('posts.payment.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('filament.customer.resources.posts.edit', ['record' => $post->getKey()]),
            'metadata' => ['post_id' => (string) $post->getKey(), 'boost' => $this->paymentBoostRequested ? 1 : 0],
        ]);

        // Save the payment reference on the post.
        $post->update(['payment_intent_id' => $session->payment_intent ?? $session->id]);

        $this->checkoutRedirectUrl = $session->url;
    }

    protected function getRedirectUrl(): string
    {
        return $this->checkoutRedirectUrl ?? parent::getRedirectUrl();
    }
}
