<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesMetaKeywords;

class TermsController extends Controller
{
    use GeneratesMetaKeywords;

    /**
     * Default SEO keywords for this controller.
     *
     * @var list<string>
     */
    protected array $defaultKeywords = ['Jobrat', 'Terms of Service', 'Conditions' ];

    public function index()
    {
        return view('terms', ['defaultKeywords' => $this->defaultKeywords]);
    }
}
