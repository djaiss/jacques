<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class StaffScope
{
    /** @return Builder<Staff> */
    public static function for(User $user, PermissionEnum $permission): Builder
    {
        if (! $permission->targetsStaff()) {
            throw new InvalidArgumentException("{$permission->value} is not about one staff member and has no list of staff members to narrow down");
        }

        // Every staff query starts inside the company of whoever is asking.
        $query = Staff::query()->where('company_id', $user->company_id);

        $scopes = $user->grants()[$permission->value] ?? [];

        if (in_array(ScopeEnum::Company, $scopes, true)) {
            return $query;
        }

        $staff = $user->staff;
        $locationIds = $staff?->activeLocations()->pluck('locations.id') ?? collect();
        $grantsLocation = in_array(ScopeEnum::Location, $scopes, true) && $locationIds->isNotEmpty();
        $grantsSelf = in_array(ScopeEnum::Self, $scopes, true) && $staff !== null;

        if (! $grantsLocation && ! $grantsSelf) {
            return $query->whereIn('id', []);
        }

        return $query->where(function (Builder $query) use ($staff, $locationIds, $grantsLocation, $grantsSelf): void {
            if ($grantsLocation) {
                $query->orWhereIn('id', LocationStaff::query()
                    ->select('staff_id')
                    ->whereIn('location_id', $locationIds));
            }

            if ($grantsSelf) {
                $query->orWhere('id', $staff?->id);
            }
        });
    }
}
