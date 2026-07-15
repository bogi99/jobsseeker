<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesMetaKeywords;
use App\Http\Requests\PublicContactRequest;
use App\Mail\PublicContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class PublicContactController extends Controller
{
    use GeneratesMetaKeywords;

    /**
     * Default SEO keywords for this controller.
     *
     * @var list<string>
     */
    protected array $defaultKeywords = ['JobRat', 'Contact', 'Contact JobRat'];

    /**
     * Display the public contact form page.
     */
    public function index(Request $request): View
    {
        $referer = $request->headers->get('referer');

        if (is_string($referer)
            && $referer !== ''
            && $referer !== url()->current()
            && str_starts_with($referer, url('/'))
        ) {
            $request->session()->put('contact_form_return_to', $referer);
        }

        return view('publiccontactform', [
            'metaKeywords' => $this->generateMetaKeywords(null, $this->defaultKeywords),
        ]);
    }

    /**
     * Handle public contact form submission.
     */
    public function submit(PublicContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $returnTo = $request->session()->get('contact_form_return_to');
        $adminContactEmail = config('mail.admin_contact_email');

        if (! is_string($returnTo) || $returnTo === '' || ! str_starts_with($returnTo, url('/'))) {
            $returnTo = route('welcome');
        }

        if (! is_string($adminContactEmail) || $adminContactEmail === '') {
            Log::error('Public contact email recipient is not configured.');

            return back()
                ->withInput()
                ->withErrors(['message' => 'Sorry, we could not send your message right now. Please try again later.']);
        }

        Log::info('Public contact form submitted.', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            Mail::to($adminContactEmail)->send(new PublicContactMessage(
                name: $validated['name'],
                email: $validated['email'],
                messageBody: $validated['message'],
                ip: $request->ip() ?? 'unknown',
                userAgent: $request->userAgent() ?? 'unknown',
            ));
        } catch (Throwable $exception) {
            Log::error('Failed to send public contact form email.', [
                'email' => $validated['email'],
                'exception' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['message' => 'Sorry, we could not send your message right now. Please try again later.']);
        }

        return back()->with([
            'contact_form_return_to' => $returnTo,
            'contact_form_sent' => true,
            'status' => 'Thanks for contacting us. We received your message.',
        ]);
    }
}
