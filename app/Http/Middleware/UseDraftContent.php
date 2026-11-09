<?php

namespace App\Http\Middleware;

use App\Content\Contracts\ContentReader;
use App\Content\Readers\DraftReader;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Swaps the published reader for the draft reader for this request only (preview links).
 * The response is marked private, not stored by any cache and not indexed.
 */
class UseDraftContent
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->instance(ContentReader::class, app(DraftReader::class));

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}
