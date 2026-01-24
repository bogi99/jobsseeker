<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesMetaKeywords;

class PrivacyController extends Controller
{
    use GeneratesMetaKeywords;

    /**
     * Default SEO keywords for this controller.
     *
     * @var list<string>
     */
    protected array $defaultKeywords = ['Jobs','JobRat', 'Privacy Policy ', 'Data Protection', 'GDPR'];

    public function index()
    {
        return view('privacy', ['defaultKeywords' => $this->defaultKeywords]);
    }
}
