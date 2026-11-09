<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every response. The public site gets a strict Content-Security-Policy with a
 * per-request nonce (scripts only from this site); the dashboard keeps the defaults its UI library needs.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $isAdmin = $request->is('admin', 'admin/*', 'livewire*', 'filament/*');
        $nonce = null;
        if (! $isAdmin) {
            $nonce = Vite::useCspNonce();
        }

        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($nonce !== null && ! $headers->has('Content-Security-Policy')) {
            $dev = is_file(public_path('hot')) ? ' '.trim((string) file_get_contents(public_path('hot'))) : '';
            $devWs = $dev !== '' ? ' '.preg_replace('#^http#', 'ws', trim($dev)) : '';
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'".$dev,
                "style-src 'self' 'unsafe-inline'".$dev,   // inline style attributes carry CMS colours and GSAP transforms
                "img-src 'self' data: blob:",
                "font-src 'self' data:".$dev,
                "connect-src 'self'".$dev.$devWs,
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
                "worker-src 'self' blob:",
            ]));
        }

        return $response;
    }
}
