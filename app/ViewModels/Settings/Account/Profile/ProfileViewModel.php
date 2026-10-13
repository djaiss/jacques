<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Profile;

use App\Models\Employee;
use App\Models\User;

class ProfileViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
    ) {}

    /** @return array{first_name: string|null, last_name: string|null, display_name: string|null, work_email: string|null} */
    public function details(): array
    {
        return [
            'first_name' => old('first_name', $this->employee?->first_name),
            'last_name' => old('last_name', $this->employee?->last_name),
            'display_name' => old('display_name', $this->employee?->display_name),
            'work_email' => old('work_email', $this->employee?->work_email),
        ];
    }

    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function hasAvatar(): bool
    {
        return $this->employee?->hasAvatar() ?? false;
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
