<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
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
            throw new InvalidArgumentException($this->permission->value.' is not about one employee and cannot be checked with forEmployee()');
        }

        // Tenant isolation comes first, before any grant is read.
        if ($this->user->company_id !== $employee->company_id) {
            return new PermissionDecision(false);
        }

        $scopes = $this->grantedScopes();

        if (in_array(ScopeEnum::Company, $scopes, true)) {
            return new PermissionDecision(true);
        }

        if (in_array(ScopeEnum::Location, $scopes, true) && $this->sharesALocationWith($employee)) {
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

    public function forLocation(Location $location): PermissionDecision
    {
        if (! $this->permission->targetsLocation()) {
            throw new InvalidArgumentException($this->permission->value.' is not about one location and cannot be checked with forLocation()');
        }

        if ($this->user->company_id !== $location->company_id) {
            return new PermissionDecision(false);
        }

        $scopes = $this->grantedScopes();

        if (in_array(ScopeEnum::Company, $scopes, true)) {
            return new PermissionDecision(true);
        }

        return new PermissionDecision(
            in_array(ScopeEnum::Location, $scopes, true)
            && $this->user->locations()->whereKey($location->id)->exists(),
        );
    }

    public function forCompany(Company $company): PermissionDecision
    {
        if ($this->permission->targetsEmployee()) {
            throw new InvalidArgumentException($this->permission->value.' is about one employee and has to be checked with forEmployee()');
        }

        if ($this->user->company_id !== $company->id) {
            return new PermissionDecision(false);
        }

        return new PermissionDecision(in_array(ScopeEnum::Company, $this->grantedScopes(), true));
    }

    /**
     * Whether the user holds the permission at any scope at all, which is what
     * decides if a screen listing several records is worth opening for them.
     */
    public function anywhere(): PermissionDecision
    {
        return new PermissionDecision($this->grantedScopes() !== []);
    }

    private function sharesALocationWith(Employee $employee): bool
    {
        return $this->user->locations()
            ->whereHas('users', fn ($query) => $query->where('employee_id', $employee->id))
            ->exists();
    }

    /** @return list<ScopeEnum> */
    private function grantedScopes(): array
    {
        return $this->user->grants()[$this->permission->value] ?? [];
    }
}
