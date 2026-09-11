<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

class SignInViewModel extends GuestViewModel
{
    public function localSignInEmail(): ?string
    {
        if (! app()->environment('local')) {
            return null;
        }

        return (string) config('jacques.seed_email');
    }
}
