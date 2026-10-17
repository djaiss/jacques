<?php

declare(strict_types=1);

namespace Tests\Unit\Permissions\StaffScopeTest;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Staff;
use App\Models\User;
use App\Permissions\StaffScope;
use InvalidArgumentException;

it('returns everybody in the company at company scope', function () {
    $company = Company::factory()->create();
    Staff::factory()->count(3)->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

    expect(StaffScope::for($user, PermissionEnum::StaffView)->count())->toEqual(3);
});

it('returns only their own record at self scope', function () {
    $company = Company::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $company->id]);
    Staff::factory()->count(3)->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self, $jim);

    $staffMembers = StaffScope::for($user, PermissionEnum::StaffUpdate)->get();

    expect($staffMembers)->toHaveCount(1);
    expect($staffMembers->first()->id)->toEqual($jim->id);
});

it('returns nobody when the user has no staff record at self scope', function () {
    $company = Company::factory()->create();
    Staff::factory()->count(3)->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    expect(StaffScope::for($user, PermissionEnum::StaffUpdate)->count())->toEqual(0);
});

it('returns nobody when no role grants the permission', function () {
    $company = Company::factory()->create();
    Staff::factory()->count(3)->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

    expect(StaffScope::for($user, PermissionEnum::StaffUpdate)->count())->toEqual(0);
});

it('never reaches into another company', function () {
    $dunderMifflin = Company::factory()->create();
    Staff::factory()->count(2)->create(['company_id' => $dunderMifflin->id]);

    $michaelScottPaperCompany = Company::factory()->create();
    Staff::factory()->count(4)->create(['company_id' => $michaelScottPaperCompany->id]);

    $user = userWith($dunderMifflin, PermissionEnum::StaffView, ScopeEnum::Company);

    $staffMembers = StaffScope::for($user, PermissionEnum::StaffView)->get();

    expect($staffMembers)->toHaveCount(2);
    expect($staffMembers->pluck('company_id')->unique()->all())->toEqual([$dunderMifflin->id]);
});

it('returns everybody in the company for the owner role', function () {
    $company = Company::factory()->create();
    Staff::factory()->count(3)->create(['company_id' => $company->id]);
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    expect(StaffScope::for($owner, PermissionEnum::StaffView)->count())->toEqual(3);
});

it('returns the colleagues of their locations at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id]);
    $dwight->locations()->attach($scranton);
    $michael = userWith($company, PermissionEnum::StaffView, ScopeEnum::Location);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);

    expect(StaffScope::for($michael, PermissionEnum::StaffView)->pluck('id')->all())->toEqualCanonicalizing([$dwight->id, $michael->staff->id]);
});

it('returns nobody through a paused assignment at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id])->locations()->attach($scranton);
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Location);
    $michael = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $michael->id]);

    expect(StaffScope::for($user, PermissionEnum::StaffView)->count())->toEqual(0);
});

it('returns nobody when the user has no staff record at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    Staff::factory()->count(2)->create(['company_id' => $company->id])
        ->each(fn (Staff $staff) => $staff->locations()->attach($scranton));
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Location);

    expect(StaffScope::for($user, PermissionEnum::StaffView)->count())->toEqual(0);
});

it('refuses a permission that covers the whole company', function () {

    $company = Company::factory()->create();
    $user = userWith($company, PermissionEnum::RoleManage, ScopeEnum::Company);

    StaffScope::for($user, PermissionEnum::RoleManage);
})->throws(InvalidArgumentException::class);

it('agrees with the check made one staff member at a time', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();

    $pam = Staff::factory()->create(['company_id' => $dunderMifflin->id]);
    $scranton = Location::factory()->create(['company_id' => $dunderMifflin->id]);
    Staff::factory()->count(3)->create(['company_id' => $dunderMifflin->id])
        ->take(2)
        ->each(fn (Staff $staff) => $staff->locations()->attach($scranton));
    LocationStaff::factory()->paused()->create([
        'location_id' => $scranton->id,
        'staff_id' => Staff::factory()->create(['company_id' => $dunderMifflin->id])->id,
    ]);
    Staff::factory()->count(3)->create(['company_id' => $michaelScottPaperCompany->id]);

    $everybody = Staff::query()->get();

    foreach (ScopeEnum::cases() as $scope) {
        $user = userWith($dunderMifflin, PermissionEnum::StaffUpdate, $scope, $pam);
        $pam->locations()->syncWithoutDetaching([$scranton->id]);
        $allowed = StaffScope::for($user, PermissionEnum::StaffUpdate)->pluck('id')->all();

        foreach ($everybody as $staff) {
            expect(in_array($staff->id, $allowed, true))->toEqual($user->permission(PermissionEnum::StaffUpdate)->forStaff($staff)->allowed(), "The list and the check disagree about staff member {$staff->id} at {$scope->value} scope");
        }
    }
});

function userWith(Company $company, PermissionEnum $permission, ScopeEnum $scope, ?Staff $staff = null): User
{
    $role = Role::factory()->create(['company_id' => $company->id]);

    RolePermission::factory()->create([
        'role_id' => $role->id,
        'permission' => $permission,
        'scope' => $scope,
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);

    $staff?->update(['user_id' => $user->id]);

    $user->roles()->attach($role->id);

    return $user;
}
