<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'auth.login';

    public function getLayout(): string
    {
        return 'layouts.auth';
    }
}
