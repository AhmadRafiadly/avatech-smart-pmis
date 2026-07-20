<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Smart-PMIS normal login flow (CEO/PM + operational roles).
 * Admin-tier users (admin / super_admin / developer) still sign in via
 * /admin/login (Filament panel) which is gated by canAccessPanel().
 */
class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectForUser(Auth::user());
        }

        return view('auth.sign-in', [
            'title' => 'Sign in',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials + ['archived_at' => null], $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Kombinasi email dan password tidak cocok.',
            ]);
        }

        // Fresh session + token so any leaked pre-auth token is invalidated.
        $request->session()->regenerate();

        return $this->redirectForUser(Auth::user());
    }

    private function redirectForUser($user): RedirectResponse
    {
        if ($user === null) {
            return redirect()->route('login');
        }

        $user->load('roles');
        $role = $user->roles->first()?->name;

        // Drop any stale /admin url.intended for non-admin-tier roles. Without
        // this, redirect()->intended() can send CEO/PM (or operational) users
        // back to /admin where canAccessPanel() then 403s them.
        $this->discardUnsafeIntendedUrl($role);

        $target = match (true) {
            $role === 'ceo_pm' => route('executive.index'),
            in_array($role, ['admin', 'super_admin', 'developer'], true) => url('/admin'),
            default => route('dashboard.index'),
        };

        return redirect()->intended($target);
    }

    private function discardUnsafeIntendedUrl(?string $role): void
    {
        // Admin-tier users are the only ones legitimately bound for /admin.
        if (in_array($role, ['admin', 'super_admin', 'developer'], true)) {
            return;
        }

        $intended = (string) session()->get('url.intended', '');
        if ($intended === '') {
            return;
        }
        $path = parse_url($intended, PHP_URL_PATH) ?: $intended;
        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            session()->forget('url.intended');
        }
    }
}
