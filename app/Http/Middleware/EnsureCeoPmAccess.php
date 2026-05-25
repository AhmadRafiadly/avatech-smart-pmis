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
        $role = $request->user()?->roles()->first()?->name;

        if (! in_array($role, self::ALLOWED_ROLES, true)) {
            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
