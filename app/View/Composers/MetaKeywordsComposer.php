<?php

namespace App\View\Composers;

use App\Http\Controllers\Concerns\GeneratesMetaKeywords;
use Illuminate\View\View;

class MetaKeywordsComposer
{
    use GeneratesMetaKeywords;

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $data = $view->getData();

        // If the controller passed activePosts, generate keywords from them.
        $activePosts = $data['activePosts'] ?? null;

        // The calling controller should provide default keywords via the view data as
        // 'defaultKeywords'. Fall back to an empty array if not provided to avoid
        // type errors (controller is recommended to provide these explicitly).
        $defaultKeywords = $data['defaultKeywords'] ?? [];

        $metaKeywords = $this->generateMetaKeywords($activePosts, $defaultKeywords);

        $view->with('metaKeywords', $metaKeywords);
    }
}
