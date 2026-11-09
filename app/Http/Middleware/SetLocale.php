<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** Applies the /{locale} prefix: sets the app language and the default for generated URLs. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');
        if (! Locales::isSupported($locale)) {
            abort(404);
        }
        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
