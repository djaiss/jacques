<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class EmployeeScope
{
    /** @return Builder<Employee> */
    public static function for(User $user, PermissionEnum $permission): Builder
    {
        if (! $permission->targetsEmployee()) {
            throw new InvalidArgumentException($permission->value.' is not about one employee and has no list of employees to narrow down');
        }

        // Every employee query starts inside the company of whoever is asking.
        $query = Employee::query()->where('company_id', $user->company_id);

        $scopes = $user->grants()[$permission->value] ?? [];

        if (in_array(ScopeEnum::Company, $scopes, true)) {
            return $query;
        }

        $locationIds = $user->locations()->pluck('locations.id');
        $grantsLocation = in_array(ScopeEnum::Location, $scopes, true) && $locationIds->isNotEmpty();
        $grantsSelf = in_array(ScopeEnum::Self, $scopes, true) && $user->employee_id !== null;

        if (! $grantsLocation && ! $grantsSelf) {
            return $query->whereIn('id', []);
        }

        return $query->where(function (Builder $query) use ($user, $locationIds, $grantsLocation, $grantsSelf): void {
            if ($grantsLocation) {
                $query->orWhereIn('id', User::query()
                    ->select('employee_id')
                    ->whereNotNull('employee_id')
                    ->whereHas('locations', fn (Builder $locations) => $locations->whereIn('locations.id', $locationIds)));
            }

            if ($grantsSelf) {
                $query->orWhere('id', $user->employee_id);
            }
        });
    }
}
