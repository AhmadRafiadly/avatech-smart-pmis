<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Send aggressive no-cache headers so authenticated pages and the login form
 * are never served from the bfcache / disk cache. Without this:
 *  - Browser Back after logout replays cached HTML of protected pages.
 *  - Cached login form keeps a stale CSRF token → POST returns 419.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Skip binary downloads (CSV exports, files, etc.) — those carry their
        // own headers and the no-store rules don't apply to them.
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (str_starts_with($contentType, 'application/octet-stream')
            || str_starts_with($contentType, 'text/csv')
            || $response->headers->has('Content-Disposition')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
