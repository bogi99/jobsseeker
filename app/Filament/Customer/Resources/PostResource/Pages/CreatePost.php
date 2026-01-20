<?php

namespace App\Filament\Customer\Resources\PostResource\Pages;

use App\Filament\Customer\Resources\PostResource;
use App\Models\Post;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Stripe\StripeClient;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    // Flags used while creating so we can trigger external checkout after the DB record is created.
    protected bool $paymentRequested = false;

    protected bool $paymentBoostRequested = false;

    // When true we will force dynamic Checkout Sessions instead of using hosted Buy Links.
    protected bool $forceDynamicCheckout = false;

    protected ?string $checkoutRedirectUrl = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        $data['user_id'] = $user?->id;

        // If the UI requested a free post via session OR the submitted payload asks for it,
        // ensure the current user is actually allowed to create free posts. Otherwise, deny.
        $formState = method_exists($this, 'form') ? $this->form->getState() : [];

        // Some test helpers or requests may pass `is_paid` as an input parameter instead of
        // as part of the Filament form state. Check request input as a final fallback.
        $requestIsPaid = (bool) request()->input('is_paid', false);

        // Determine if this request is operating in the free flow. We prefer session flag set by
        // the `EnsureUserHasFreeAccess` middleware, falling back to submitted form state.
        $isRequestedFree = (bool) (session('customer_free_flow', false) || ($data['is_free'] ?? false) || ($formState['is_free'] ?? false));

        \Illuminate\Support\Facades\Log::info('mutateFormDataBeforeCreate', ['requestedPaid' => (bool) ($data['is_paid'] ?? false) || (bool) ($formState['is_paid'] ?? false) || $requestIsPaid, 'formState' => $formState, 'data' => $data, 'requestIsPaid' => $requestIsPaid]);

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
            // If a caller (like `createAndPay`) has pre-set `$this->paymentRequested`, respect it so
            // programmatic flows can force the paid/dynamic-checkout behavior.
            $requestedPaid = $this->paymentRequested || (bool) ($data['is_paid'] ?? false) || (bool) ($formState['is_paid'] ?? false) || $requestIsPaid;

            if ($requestedPaid) {
                // Mark the DB record as pending payment and not active. We'll create a Checkout Session
                // in afterCreate and redirect the user to Stripe.
                $this->paymentRequested = true;

                // If a caller pre-set `paymentBoostRequested` keep it; otherwise derive from inputs.
                $this->paymentBoostRequested = $this->paymentBoostRequested || (bool) ($data['is_featured'] ?? false) || (bool) ($formState['is_featured'] ?? false);

                $data['is_paid'] = false;
                $data['is_active'] = false;
                $data['payment_status'] = 'pending';
            } else {
                // Non-free users: always save as draft until payment or activation.
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

        // If we have a configured hosted buy link, prefer redirecting the user there instead
        // of creating a dynamic Checkout Session — unless a caller explicitly forced dynamic
        // checkout (e.g., the Create & Pay button). This ensures test-friendly behavior.
        // Prefer hosted buy links only when a hosted URL is configured and we do NOT have
        // a bound Stripe client available (e.g. in tests we bind a mock client and want
        // to exercise the dynamic Checkout Session flow).
        if (! $this->forceDynamicCheckout && ($url = $this->getHostedUrl($this->paymentBoostRequested)) && ! app()->bound(\Stripe\StripeClient::class)) {
            // Optionally store a placeholder reference if needed. For now we leave it null.
            $post->update(['payment_intent_id' => null]);

            $this->checkoutRedirectUrl = $url;

            return;
        }

        // Create a Stripe Checkout Session for the pending post. Use the container binding when
        // resolving the client so tests can swap the implementation.
        // Resolve the client from the container so tests can substitute a mock instance.
        $client = app()->make(StripeClient::class);

        $amount = config('services.stripe.price_post', 5000);
        $boostAmount = config('services.stripe.price_post_boost', 9000);

        $unitAmount = $this->paymentBoostRequested ? $boostAmount : $amount;

        // Build metadata and log what we're sending so we can debug missing metadata issues.
        $metadata = ['post_id' => (string) $post->getKey(), 'boost' => $this->paymentBoostRequested ? 1 : 0];

        \Illuminate\Support\Facades\Log::info('Creating Stripe Checkout Session', ['post_id' => $post->getKey(), 'metadata' => $metadata, 'amount' => $unitAmount]);

        // Log the payload we will send to Stripe so we can inspect it if metadata is missing.
        \Illuminate\Support\Facades\Log::info('Stripe checkout payload', [
            'post_id' => $post->getKey(),
            'line_items' => [[
                'currency' => config('services.stripe.currency', 'cad'),
                'product_name' => $this->paymentBoostRequested ? 'Posting + Boost' : 'Posting',
                'unit_amount' => $unitAmount,
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('posts.payment.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('filament.customer.resources.posts.edit', ['record' => $post->getKey()]),
            'metadata' => $metadata,
        ]);

        $session = $client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'cad'),
                    'product_data' => ['name' => $this->paymentBoostRequested ? 'Posting + Boost' : 'Posting'],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('posts.payment.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('filament.customer.resources.posts.edit', ['record' => $post->getKey()]),
            'metadata' => $metadata,
        ]);

        // Save the payment reference on the transaction and the post.
        $transaction = \App\Models\Transaction::create([
            'user_id' => Filament::auth()->user()?->id,
            'post_id' => $post->getKey(),
            'stripe_session_id' => $session->id ?? null,
            'payment_intent_id' => $session->payment_intent ?? null,
            'amount' => $unitAmount,
            'currency' => config('services.stripe.currency', 'cad'),
            'status' => \App\Models\Transaction::STATUS_PENDING,
            'metadata' => $metadata,
            'expires_at' => now()->addDay(),
        ]);

        $post->update(['payment_intent_id' => $session->payment_intent ?? $session->id]);

        $this->checkoutRedirectUrl = $session->url;

        // Log the redirect URL so you can see the dynamic stripe link we will send the user to.
        \Illuminate\Support\Facades\Log::info('Redirecting to Stripe Checkout URL', ['post_id' => $post->getKey(), 'session_id' => $session->id ?? null, 'url' => $this->checkoutRedirectUrl, 'transaction_id' => $transaction->id]);

        \Illuminate\Support\Facades\Log::info('Stripe Checkout Session created', ['session_id' => $session->id ?? null, 'payment_intent' => $session->payment_intent ?? null, 'url' => $session->url ?? null, 'metadata' => $metadata, 'transaction_id' => $transaction->id]);
    }

    protected function getHostedUrl(bool $boost): ?string
    {
        return $boost ? config('services.stripe.hosted_post_boost_url') : config('services.stripe.hosted_post_url');
    }

    public function mount(): void
    {
        parent::mount();

        if ($this->userHasPendingTransaction()) {
            Notification::make()
                ->warning()
                ->title('Pending payment in progress')
                ->body('You already have a pending payment. New purchases are disabled until it completes.')
                ->send();
        }
    }

    protected function userHasPendingTransaction(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return \App\Models\Transaction::where('user_id', $user->id)
            ->where('status', \App\Models\Transaction::STATUS_PENDING)
            ->exists();
    }

    /**
     * Create a draft post from current form state and redirect the user to a hosted buy link.
     */
    protected function saveDraftAndRedirect(bool $boost): void
    {
        if ($this->userHasPendingTransaction()) {
            \Illuminate\Support\Facades\Log::info('User attempted hosted purchase but already has pending transaction', ['user_id' => Filament::auth()->id()]);

            // Notify the user we blocked the action due to a pending transaction
            Notification::make()
                ->warning()
                ->title('Pending payment in progress')
                ->body('You already have a pending payment. New purchases are disabled until it completes.')
                ->send();

            // Redirect back to posts list to prevent creating duplicate pending transactions.
            $this->redirect(route('filament.customer.resources.posts.index'));

            return;
        }

        $state = $this->form->getState();

        $state['user_id'] = Filament::auth()->user()?->id;
        $state['is_free'] = false;
        $state['is_featured'] = $boost;
        $state['is_paid'] = false;
        $state['is_active'] = false;
        $state['payment_status'] = 'pending';

        $post = Post::create(collect($state)->only([
            'user_id',
            'title',
            'content',
            'full_content',
            'is_active',
            'is_paid',
            'is_featured',
            'is_free',
            'payment_intent_id',
            'payment_status',
            'company_name',
            'company_logo',
            'application_link',
        ])->toArray());

        if (! empty($state['tags'])) {
            $post->tags()->sync($state['tags']);
        }

        $url = $this->getHostedUrl($boost) ?? '/';

        // Create a transaction record to represent this pending purchase. This gives us a
        // server-side handle to reconcile webhook events from static hosted links.
        $transaction = \App\Models\Transaction::create([
            'user_id' => Filament::auth()->user()?->id,
            'post_id' => $post->getKey(),
            'amount' => config('services.stripe.price_post'),
            'currency' => config('services.stripe.currency', 'cad'),
            'status' => \App\Models\Transaction::STATUS_PENDING,
            'metadata' => ['source' => 'hosted_link'],
            'expires_at' => now()->addDays(7),
        ]);

        // Append the post id and a short transaction id to help the success handler identify this draft
        // (Stripe Payment Links are static, so this is a best-effort fallback). Use transaction id too.
        if ($post) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url = $url.$separator.'post_id='.$post->getKey().'&tx='.$transaction->id;
        }

        // Log the hosted buy link we will redirect to as well (best-effort fallback).
        \Illuminate\Support\Facades\Log::info('Redirecting to hosted Buy Link', ['post_id' => $post->getKey(), 'transaction_id' => $transaction->id, 'url' => $url]);

        // Use Livewire's redirect helper which returns a Livewire Redirector and works inside actions.
        $this->redirect($url);
    }

    // Public Livewire wrapper so the form's buttons can call into the page to trigger
    // saving a draft and redirecting to the hosted Stripe link.
    public function payHosted($boost): void
    {
        $this->saveDraftAndRedirect((bool) $boost);
    }

    /**
     * Create the record and initiate a dynamic Stripe Checkout Session for payment.
     */
    public function createAndPay(bool $boost = false): void
    {
        // Prevent creating a new checkout if the user already has a pending transaction.
        if ($this->userHasPendingTransaction()) {
            \Illuminate\Support\Facades\Log::info('User attempted Create & Pay but already has pending transaction', ['user_id' => Filament::auth()->id()]);

            // Notify the user we blocked the action due to a pending transaction
            Notification::make()
                ->warning()
                ->title('Pending payment in progress')
                ->body('You already have a pending payment. New purchases are disabled until it completes.')
                ->send();

            $this->redirect(route('filament.customer.resources.posts.index'));

            return;
        }

        // Build a draft post from the current form state so we can create a Checkout Session
        // with metadata containing the new post id (this mirrors `saveDraftAndRedirect`).
        $state = $this->form->getState();

        $state['user_id'] = Filament::auth()->user()?->id;
        $state['is_free'] = false;
        $state['is_featured'] = $boost;
        $state['is_paid'] = false;
        $state['is_active'] = false;
        $state['payment_status'] = 'pending';

        $post = Post::create(collect($state)->only([
            'user_id',
            'title',
            'content',
            'full_content',
            'is_active',
            'is_paid',
            'is_featured',
            'is_free',
            'payment_intent_id',
            'payment_status',
            'company_name',
            'company_logo',
            'application_link',
        ])->toArray());

        if (! empty($state['tags'])) {
            $post->tags()->sync($state['tags']);
        }

        // Create Stripe Checkout Session dynamically so we can attach metadata.post_id.
        $client = app()->make(StripeClient::class);

        $amount = config('services.stripe.price_post', 5000);
        $boostAmount = config('services.stripe.price_post_boost', 9000);

        $unitAmount = $boost ? $boostAmount : $amount;

        $metadata = ['post_id' => (string) $post->getKey(), 'boost' => $boost ? 1 : 0];

        $session = $client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'cad'),
                    'product_data' => ['name' => $boost ? 'Posting + Boost' : 'Posting'],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('posts.payment.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('filament.customer.resources.posts.edit', ['record' => $post->getKey()]),
            'metadata' => $metadata,
        ]);

        // Save payment reference on the post.
        $post->update(['payment_intent_id' => $session->payment_intent ?? $session->id]);

        // Create a transaction record so dynamic Checkout Sessions are also reconciled reliably.
        $transaction = \App\Models\Transaction::create([
            'user_id' => $post->user_id,
            'post_id' => $post->getKey(),
            'stripe_session_id' => $session->id ?? null,
            'payment_intent_id' => $session->payment_intent ?? null,
            'amount' => $unitAmount,
            'currency' => config('services.stripe.currency', 'cad'),
            'status' => \App\Models\Transaction::STATUS_PENDING,
            'metadata' => $metadata,
            'expires_at' => now()->addDay(),
        ]);

        \Illuminate\Support\Facades\Log::info('Created transaction for dynamic checkout', ['post_id' => $post->getKey(), 'transaction_id' => $transaction->id ?? null, 'session_id' => $session->id ?? null]);

        \Illuminate\Support\Facades\Log::info('Redirecting to Stripe Checkout URL', ['post_id' => $post->getKey(), 'session_id' => $session->id ?? null, 'url' => $session->url ?? null, 'metadata' => $metadata, 'transaction_id' => $transaction->id ?? null]);

        $this->redirect($session->url);
    }

    /**
     * Add the pay buttons inline with the form actions so they appear next to Create / Create another / Cancel.
     *
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getFormActions(): array
    {
        // Build the action list with primary Create first, then our pay buttons, then optional Create another, then Cancel
        return [
            $this->getCreateFormAction(),

            // Dynamic Checkout Sessions (create & pay) — preferred for automatic activation via webhook
            \Filament\Actions\Action::make('create_and_pay')
                ->label('Create & Pay')
                ->action(fn () => $this->createAndPay(false))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.key'))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('success'),

            \Filament\Actions\Action::make('create_and_pay_boost')
                ->label('Create & Pay + Boost')
                ->action(fn () => $this->createAndPay(true))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.key'))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('success'),

            // Hosted buy links (legacy/static links) — fallback flow
            \Filament\Actions\Action::make('pay')
                ->label('Pay to post (Hosted)')
                ->action(fn () => $this->saveDraftAndRedirect(false))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.hosted_post_url'))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('secondary'),

            \Filament\Actions\Action::make('pay_boost')
                ->label('Pay + Boost (Hosted)')
                ->action(fn () => $this->saveDraftAndRedirect(true))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.hosted_post_boost_url'))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('secondary'),

            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),

            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->checkoutRedirectUrl ?? parent::getRedirectUrl();
    }
}
