<?php

declare(strict_types=1);

namespace Tests;

use App\Actions\CreateDefaultRoles;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function grant(User $user, PermissionEnum $permission, ScopeEnum $scope = ScopeEnum::Company): User
    {
        $role = $user->roles()->first() ?? Role::query()->create([
            'company_id' => $user->company_id,
            'name' => $permission->value.' at '.$scope->value.' scope',
            'slug' => Str::slug($permission->value.'-'.$scope->value),
            'is_default' => false,
            'is_editable' => true,
        ]);

        RolePermission::query()->create([
            'role_id' => $role->id,
            'permission' => $permission,
            'scope' => $scope,
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->forgetGrants();

        return $user;
    }

    protected function giveDefaultRole(User $user, string $slug): User
    {
        $company = $user->company;

        if (! $company->roles()->exists()) {
            new CreateDefaultRoles(company: $company)->execute();
        }

        $role = $company->roles()->where('slug', $slug)->firstOrFail();

        $user->roles()->sync([$role->id]);
        $user->forgetGrants();

        return $user;
    }
}
