<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;

class PostObserver
{
    public function updating(Post $post): void
    {
        if (! $post->isDirty('is_active')) {
            return;
        }

        $original = $post->getOriginal('is_active');
        $new = $post->is_active;

        if ($original === true && $new === false) {
            // Pause in-place (do not call pause() which would save and re-trigger observer)
            // If the paused_remaining_seconds was already calculated by the caller (pause()), keep it.
            if (! $post->isDirty('paused_remaining_seconds')) {
                if ($post->expires_at && now()->lt($post->expires_at)) {
                    $post->paused_remaining_seconds = $post->expires_at->diffInSeconds(now());
                } else {
                    $post->paused_remaining_seconds = null;
                }
            }

            $post->published_at = null;
            $post->expires_at = null;

            // Ensure attribute remains false (is_active already set by caller)
            return;
        }

        if ($original === false && $new === true) {
            // Attempt to resume or activate
            if ($post->is_free) {
                if ($post->paused_remaining_seconds && $post->paused_remaining_seconds > 0) {
                    $post->published_at = now();
                    $post->expires_at = now()->addSeconds($post->paused_remaining_seconds);
                    $post->paused_remaining_seconds = null;
                    // is_active already set true by caller

                    return;
                }

                // Fresh 30-day window
                $post->published_at = now();
                $post->expires_at = now()->addDays(30);

                return;
            }

            if ($post->is_paid) {
                if ($post->paused_remaining_seconds && $post->paused_remaining_seconds > 0) {
                    $post->published_at = now();
                    $post->expires_at = now()->addSeconds($post->paused_remaining_seconds);
                    $post->paused_remaining_seconds = null;

                    return;
                }

                // If the expires_at has been explicitly set (e.g. resume() set it) and is in the future,
                // allow the activation.
                if ($post->expires_at && now()->lte($post->expires_at)) {
                    return;
                }

                // Otherwise reactivation requires payment (do not allow)
                throw new AuthorizationException('Post has expired and requires payment to reactivate.');
            }

            // Default: treat as free activation
            $post->published_at = now();
            $post->expires_at = now()->addDays(30);

            return;
        }
    }
}
