<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LegalController extends Controller
{
    public function mentionsLegales()
    {
        return Inertia::render('Legal/MentionsLegales');
    }

    public function cgu()
    {
        return Inertia::render('Legal/CGU');
    }

    public function privacyPolicy()
    {
        return Inertia::render('Legal/PrivacyPolicy');
    }
}
