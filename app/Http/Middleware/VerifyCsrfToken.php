<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Stripe will POST webhooks here; exclude it explicitly to avoid CSRF errors.
        // Include both with and without leading slash to avoid mismatch.
        'webhooks/stripe',
        '/webhooks/stripe',
    ];
}
