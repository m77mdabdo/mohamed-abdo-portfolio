<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "/" sends visitors to their language (Accept-Language), Arabic by default. */
class LocaleRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = Locales::fromAcceptLanguage($request->header('Accept-Language'));

        return redirect()->route('home', ['locale' => $locale], 302)->setVary('Accept-Language');
    }
}
