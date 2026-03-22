<?php

namespace App\Filament\Customer\Resources\PostResource\Pages;

use App\Filament\Customer\Resources\PostResource;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class EditPost extends EditRecord
{
    use \Illuminate\Support\Traits\Macroable;

    protected static string $resource = PostResource::class;

    // payment tracking flags (copied from CreatePost) ----------------------------------
    protected bool $paymentRequested = false;

    protected bool $paymentBoostRequested = false;

    protected bool $forceDynamicCheckout = false;

    protected ?string $checkoutRedirectUrl = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

    // helpers reused from CreatePost but modifying an existing record --------------------
    protected function userHasPendingTransaction(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return \App\Models\Transaction::where('user_id', $user->id)
            ->where('status', \App\Models\Transaction::STATUS_PENDING)
            ->exists();
    }

    protected function prepareRecordForPayment(bool $boost): void
    {
        $post = $this->getRecord();
        $state = $this->form->getState();

        // update core attributes from form state
        $post->update(array_merge(
            [
                'is_free' => false,
                'is_featured' => $boost,
                'is_paid' => false,
                'is_active' => false,
                'payment_status' => 'pending',
            ],
            Arr::only($state, [
                'title',
                'content',
                'full_content',
                'company_name',
                'company_logo',
                'application_link',
            ])
        ));

        if (! empty($state['tags'])) {
            $post->tags()->sync($state['tags']);
        }
    }

    public function payHosted($boost): void
    {
        // identical flow to CreatePost::saveDraftAndRedirect but working on existing post
        if ($this->userHasPendingTransaction()) {
            Notification::make()
                ->warning()
                ->title('Pending payment in progress')
                ->body('You already have a pending payment. New purchases are disabled until it completes.')
                ->send();

            $this->redirect(route('filament.customer.resources.posts.index'));

            return;
        }

        $this->prepareRecordForPayment((bool) $boost);

        $post = $this->getRecord();
        $url = $this->getHostedUrl((bool) $boost) ?? '/';

        $transaction = \App\Models\Transaction::create([
            'user_id' => Auth::id(),
            'post_id' => $post->getKey(),
            'amount' => config('services.stripe.price_post'),
            'currency' => config('services.stripe.currency', 'cad'),
            'status' => \App\Models\Transaction::STATUS_PENDING,
            'metadata' => ['source' => 'hosted_link'],
            'expires_at' => now()->addDays(7),
        ]);

        if ($post) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url = $url.$separator.'post_id='.$post->getKey().'&tx='.$transaction->id;
        }

        \Illuminate\Support\Facades\Log::info('Redirecting to hosted Buy Link', ['post_id' => $post->getKey(), 'transaction_id' => $transaction->id, 'url' => $url]);

        $this->redirect($url);
    }

    public function createAndPay(bool $boost = false): void
    {
        if ($this->userHasPendingTransaction()) {
            Notification::make()
                ->warning()
                ->title('Pending payment in progress')
                ->body('You already have a pending payment. New purchases are disabled until it completes.')
                ->send();

            $this->redirect(route('filament.customer.resources.posts.index'));

            return;
        }

        $this->prepareRecordForPayment($boost);

        $post = $this->getRecord();

        $client = app()->make(\Stripe\StripeClient::class);

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

        $post->update(['payment_intent_id' => $session->payment_intent ?? $session->id]);

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

        \Illuminate\Support\Facades\Log::info('Redirecting to Stripe Checkout URL', ['post_id' => $post->getKey(), 'session_id' => $session->id ?? null, 'url' => $session->url ?? null, 'metadata' => $metadata, 'transaction_id' => $transaction->id]);

        $this->redirect($session->url);
    }

    protected function getHostedUrl(bool $boost): ?string
    {
        return $boost ? config('services.stripe.hosted_post_boost_url') : config('services.stripe.hosted_post_url');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),

            \Filament\Actions\Action::make('create_and_pay')
                ->label('Pay & Save')
                ->action(fn () => $this->createAndPay(false))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.key') && (! $this->getRecord() || ! $this->getRecord()->is_paid))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('success'),

            \Filament\Actions\Action::make('create_and_pay_boost')
                ->label('Pay + Boost & Save')
                ->action(fn () => $this->createAndPay(true))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.key') && (! $this->getRecord() || ! $this->getRecord()->is_paid))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('success'),

            \Filament\Actions\Action::make('pay')
                ->label('Pay to post (Hosted)')
                ->action(fn () => $this->payHosted(false))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.hosted_post_url') && (! $this->getRecord() || ! $this->getRecord()->is_paid))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('secondary'),

            \Filament\Actions\Action::make('pay_boost')
                ->label('Pay + Boost (Hosted)')
                ->action(fn () => $this->payHosted(true))
                ->visible(fn (): bool => ! Filament::auth()->user()?->is_free && (bool) config('services.stripe.hosted_post_boost_url') && (! $this->getRecord() || ! $this->getRecord()->is_paid))
                ->disabled(fn (): bool => $this->userHasPendingTransaction())
                ->color('secondary'),

            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->checkoutRedirectUrl ?? parent::getRedirectUrl();
    }
}
