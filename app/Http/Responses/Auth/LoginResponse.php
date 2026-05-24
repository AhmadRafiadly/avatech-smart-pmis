<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as Contract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Contract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        /*
         * This response only fires through the Filament panel login flow,
         * which is restricted by User::canAccessPanel() to admin /
         * super_admin / developer. Send those users to /admin.
         *
         * Non-admin roles (CEO/PM, operational) never reach this point
         * because they sign in via the standalone /login flow handled
         * by App\Http\Controllers\Auth\LoginController.
         */
        $intended = (string) session('url.intended');
        $intendedPath = parse_url($intended, PHP_URL_PATH) ?: $intended;
        if ($intendedPath !== '' && ! str_starts_with($intendedPath, '/admin/login')) {
            session()->forget('url.intended');
        }

        $role = auth()->user()?->roles?->first()?->name;

        $url = match (true) {
            in_array($role, ['admin', 'super_admin', 'developer'], true) => url('/admin'),
            $role === 'ceo_pm' => route('executive.index'),
            default            => route('dashboard.index'),
        };

        return redirect()->to($url);
    }
}
