<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'post_id',
        'stripe_session_id',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function markCompleted(?string $paymentIntent = null, ?string $sessionId = null): void
    {
        $this->update(['status' => self::STATUS_COMPLETED, 'payment_intent_id' => $paymentIntent ?? $this->payment_intent_id, 'stripe_session_id' => $sessionId ?? $this->stripe_session_id]);
    }
}
