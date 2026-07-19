<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCeoPmAccess
{
    private const ALLOWED_ROLES = ['ceo_pm', 'admin', 'super_admin', 'developer'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasAnyRole(self::ALLOWED_ROLES)) {
            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
