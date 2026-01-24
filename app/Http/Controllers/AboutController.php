<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesMetaKeywords;

class AboutController extends Controller
{
    use GeneratesMetaKeywords;

    /**
     * Default SEO keywords for this controller.
     *
     * @var list<string>
     */
    protected array $defaultKeywords = ['JobRat', 'About', 'About JobRat' ];

    public function index()
    {
        return view('about', ['defaultKeywords' => $this->defaultKeywords]);
    }
}
