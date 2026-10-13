<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use InvalidArgumentException;

class PendingPermissionCheck
{
    public function __construct(
        private readonly User $user,
        private readonly PermissionEnum $permission,
    ) {}

    public function forEmployee(Employee $employee): PermissionDecision
    {
        if (! $this->permission->targetsEmployee()) {
            throw new InvalidArgumentException($this->permission->value.' covers the whole company and has to be checked with forCompany()');
        }

        // Tenant isolation comes first, the owner bypass included.
        if ($this->user->company_id !== $employee->company_id) {
            return new PermissionDecision(false);
        }

        if ($this->ownsTheCompany()) {
            return new PermissionDecision(true);
        }

        $scopes = $this->grantedScopes();

        if (in_array(ScopeEnum::Company, $scopes, true)) {
            return new PermissionDecision(true);
        }

        if (in_array(ScopeEnum::Self, $scopes, true)) {
            // An account behind no employee has no record of its own, so self
            // covers nothing for it.
            return new PermissionDecision(
                $this->user->employee_id !== null && $this->user->employee_id === $employee->id,
            );
        }

        return new PermissionDecision(false);
    }

    public function forCompany(Company $company): PermissionDecision
    {
        if ($this->permission->targetsEmployee()) {
            throw new InvalidArgumentException($this->permission->value.' is about one employee and has to be checked with forEmployee()');
        }

        if ($this->user->company_id !== $company->id) {
            return new PermissionDecision(false);
        }

        if ($this->ownsTheCompany()) {
            return new PermissionDecision(true);
        }

        return new PermissionDecision(in_array(ScopeEnum::Company, $this->grantedScopes(), true));
    }

    private function ownsTheCompany(): bool
    {
        return $this->user->company->owner_user_id === $this->user->id;
    }

    /** @return list<ScopeEnum> */
    private function grantedScopes(): array
    {
        return $this->user->grants()[$this->permission->value] ?? [];
    }
}
