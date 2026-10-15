<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Profile;

use App\Models\Staff;
use App\Models\User;

class ProfileViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Staff $staff,
    ) {}

    /** @return array{first_name: string|null, last_name: string|null, display_name: string|null, work_email: string|null} */
    public function details(): array
    {
        return [
            'first_name' => old('first_name', $this->staff?->first_name),
            'last_name' => old('last_name', $this->staff?->last_name),
            'display_name' => old('display_name', $this->staff?->display_name),
            'work_email' => old('work_email', $this->staff?->email),
        ];
    }

    public function name(): string
    {
        return $this->staff->name ?? $this->user->email;
    }

    public function staff(): ?Staff
    {
        return $this->staff;
    }

    public function hasAvatar(): bool
    {
        return $this->staff?->hasAvatar() ?? false;
    }

    public function email(): string
    {
        return $this->user->email;
    }

    public function companyName(): string
    {
        return $this->user->company->name;
    }
}
