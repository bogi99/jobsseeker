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
        'company_name',
        'company_logo',
        'application_link',
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
}
