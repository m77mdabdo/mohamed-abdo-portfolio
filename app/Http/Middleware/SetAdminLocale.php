<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Dashboard language: the signed-in user's choice, otherwise the session choice, otherwise Arabic. */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user() !== null ? $request->user()->locale : $request->session()->get('admin_locale');
        app()->setLocale(Locales::isSupported($locale) ? $locale : 'ar');

        return $next($request);
    }
}
