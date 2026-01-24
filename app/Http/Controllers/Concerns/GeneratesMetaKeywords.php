<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;

trait GeneratesMetaKeywords
{
    /**
     * Build a comma-separated meta keywords string from selected posts and default keywords.
     *
     * @param  Collection|null  $posts  A collection of Post models that may have a "tags" relation
     * @param  string[]  $defaultKeywords  Default keywords to anchor site SEO (required)
     * @param  int|null  $maxTags  Optional maximum number of tag keywords to include
     * @param  int|null  $maxChars  Optional maximum total characters for the final string
     */
    public function generateMetaKeywords(?Collection $posts, array $defaultKeywords, ?int $maxTags = null, ?int $maxChars = null): string
    {
        // Collect distinct tag names from the posts collection
        $tagNames = collect();

        if ($posts) {
            $tagNames = $posts->pluck('tags')->flatten()->pluck('name')->filter()->unique()->values();

            if ($maxTags) {
                $tagNames = $tagNames->slice(0, $maxTags);
            }
        }

        // Merge defaults with tag names and ensure uniqueness
        $keywords = collect($defaultKeywords)->merge($tagNames)->unique()->values();

        $meta = $keywords->implode(', ');

        if ($maxChars && strlen($meta) > $maxChars) {
            // Trim while preserving whole keywords when possible
            $truncated = '';

            foreach ($keywords as $word) {
                $candidate = $truncated === '' ? $word : $truncated.', '.$word;

                if (strlen($candidate) > $maxChars) {
                    break;
                }

                $truncated = $candidate;
            }

            $meta = $truncated ?: substr($meta, 0, $maxChars);
        }

        return $meta;
    }
}
