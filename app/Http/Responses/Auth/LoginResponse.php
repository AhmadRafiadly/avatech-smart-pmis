<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as Contract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Contract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $role = auth()->user()?->roles?->first()?->name;

        $url = $role === 'ceo_pm'
            ? route('executive.index')
            : route('dashboard.index');

        return redirect()->to($url);
    }
}
