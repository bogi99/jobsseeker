<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
        'paid_at',
        'published_at',
        'expires_at',
        'paused_remaining_seconds',
        'company_name',
        'company_logo',
        'application_link',
    ];

    /**
     * The attribute casts.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_paid' => 'boolean',
        'is_featured' => 'boolean',
        'is_free' => 'boolean',
        'paid_at' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'paused_remaining_seconds' => 'integer',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = [
        'company_logo_url',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The tags that belong to the post.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function getCompanyLogoUrlAttribute(): ?string
    {
        if (! $this->company_logo) {
            return null;
        }

        return Storage::disk('public')->url($this->company_logo);
    }

    /**
     * Normalize and persist the `application_link` value.
     *
     * Behavior:
     * - Ensure mailto links are stored using `mailto://` (user requested visual distinction)
     * - Preserve other schemes (http(s):) as provided
     * - Convert plain emails to `mailto://{email}`
     * - Prepend `https://` for bare hostnames/URLs without a scheme
     */
    public function setApplicationLinkAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['application_link'] = null;

            return;
        }

        $value = trim($value);

        // Normalize any mailto variant to use double-slash form `mailto://`
        if (preg_match('/^mailto:(?:\/\/)?/i', $value)) {
            $value = preg_replace('/^mailto:(?:\/\/)?/i', 'mailto://', $value);

            $this->attributes['application_link'] = $value;

            return;
        }

        // If value already contains a scheme (http:, https:, etc.) keep as-is
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $value)) {
            $this->attributes['application_link'] = $value;

            return;
        }

        // If it looks like an email address, convert to mailto://
        if (str_contains($value, '@')) {
            $this->attributes['application_link'] = 'mailto://'.$value;

            return;
        }

        // Otherwise assume it's a website and prepend https://
        $this->attributes['application_link'] = 'https://'.$value;
    }

    /**
     * Activate a post as a paid post (called on successful payment).
     */
    public function activateAsPaid(bool $boost = false): void
    {
        $this->is_paid = true;
        $this->payment_status = 'paid';
        $this->paid_at = now();
        $this->is_active = true;
        $this->published_at = now();
        $this->expires_at = now()->addDays(30);
        $this->paused_remaining_seconds = null;

        if ($boost) {
            $this->is_featured = true;
        }

        $this->save();
    }

    /**
     * Activate a post as a free post (free users).
     * We resume paused time if available, otherwise grant a fresh 30-day window.
     */
    public function activateAsFree(): void
    {
        $this->is_free = true;
        $this->is_paid = false;
        $this->payment_status = 'free';
        $this->is_active = true;
        $this->published_at = now();

        if ($this->paused_remaining_seconds) {
            $this->expires_at = now()->addSeconds($this->paused_remaining_seconds);
            $this->paused_remaining_seconds = null;
        } else {
            $this->expires_at = now()->addDays(30);
        }

        $this->save();
    }

    /**
     * Pause a post (user deactivated). Store remaining seconds so it can be resumed.
     */
    public function pause(): void
    {
        $remaining = null;

        if ($this->expires_at) {
            $difference = $this->expires_at->diffInSeconds(now());

            if ($difference > 0) {
                $remaining = $difference;
            }
        }

        // Perform a direct DB update to avoid observer recursion and ensure values are persisted atomically.
        \Illuminate\Support\Facades\DB::table('posts')
            ->where('id', $this->id)
            ->update([
                'is_active' => false,
                'published_at' => null,
                'expires_at' => null,
                'paused_remaining_seconds' => $remaining,
                'updated_at' => now(),
            ]);

        $this->refresh();

        // Ensure model instance reflects the computed remaining value in memory (defensive).
        $this->paused_remaining_seconds = $remaining;
    }

    /**
     * Resume a paused post. Restores remaining time if available.
     */
    public function resume(): void
    {
        if ($this->paused_remaining_seconds && $this->paused_remaining_seconds > 0) {
            $this->published_at = now();
            $this->expires_at = now()->addSeconds($this->paused_remaining_seconds);
            $this->paused_remaining_seconds = null;
            $this->is_active = true;
            $this->save();

            return;
        }

        // If there's no paused remaining seconds, treat as fresh activation depending on free/paid
        if ($this->is_free) {
            $this->activateAsFree();

            return;
        }

        // For paid posts without paused time, reactivation requires valid paid state and won't be allowed here.
        throw new \Illuminate\Auth\Access\AuthorizationException('Reactivation requires payment');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }
}
