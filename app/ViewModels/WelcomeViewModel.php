<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\User;

class WelcomeViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function firstName(): string
    {
        return $this->user->employee->first_name ?? $this->user->email;
    }
}
