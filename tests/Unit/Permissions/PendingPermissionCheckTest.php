<?php

declare(strict_types=1);

namespace Tests\Unit\Permissions\PendingPermissionCheckTest;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

it('allows a staff member of the company at company scope', function () {
    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $michael = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    expect($michael->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed())->toBeTrue();
});

it('allows only their own record at self scope', function () {
    $company = Company::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $company->id]);
    $pam = Staff::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self, $jim);

    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($jim)->allowed())->toBeTrue();
    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($pam)->allowed())->toBeFalse();
});

it('never matches self when the user has no staff record', function () {
    $company = Company::factory()->create();
    $creed = Staff::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    expect($user->staff)->toBeNull();
    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($creed)->allowed())->toBeFalse();
});

it('denies when no role grants the permission', function () {
    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed())->toBeFalse();
});

it('denies a staff member of another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
    $user = userWith($dunderMifflin, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($stranger)->allowed())->toBeFalse();
});

it('allows the owner role everything inside their own company', function () {
    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    expect($owner->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed())->toBeTrue();
    expect($owner->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed())->toBeTrue();
    expect($owner->permission(PermissionEnum::BillingManage)->forCompany($company)->allowed())->toBeTrue();
});

it('denies the owner role outside their own company', function () {
    $dunderMifflin = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $dunderMifflin->id]), Role::OWNER);

    $michaelScottPaperCompany = Company::factory()->create();
    $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);

    expect($owner->permission(PermissionEnum::StaffView)->forStaff($stranger)->allowed())->toBeFalse();
    expect($owner->permission(PermissionEnum::CompanyManage)->forCompany($michaelScottPaperCompany)->allowed())->toBeFalse();
});

it('allows a location the user is assigned to at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $stamford = Location::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id])->locations()->attach($scranton);

    expect($user->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed())->toBeTrue();
    expect($user->permission(PermissionEnum::LocationManage)->forLocation($stamford)->allowed())->toBeFalse();
});

it('allows every location of the company at company scope', function () {
    $company = Company::factory()->create();
    $stamford = Location::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::LocationManage, ScopeEnum::Company);

    expect($user->permission(PermissionEnum::LocationManage)->forLocation($stamford)->allowed())->toBeTrue();
});

it('denies a location of another company', function () {
    $company = Company::factory()->create();
    $nashua = Location::factory()->create();
    $user = userWith($company, PermissionEnum::LocationManage, ScopeEnum::Company);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id])->locations()->attach($nashua);

    expect($user->permission(PermissionEnum::LocationManage)->forLocation($nashua)->allowed())->toBeFalse();
});

it('allows a colleague who shares a location at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $stamford = Location::factory()->create(['company_id' => $company->id]);
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $andy = Staff::factory()->create(['company_id' => $company->id]);
    $dwight->locations()->attach($scranton);
    $andy->locations()->attach($stamford);
    $michael = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Location);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);

    expect($michael->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed())->toBeTrue();
    expect($michael->permission(PermissionEnum::StaffUpdate)->forStaff($andy)->allowed())->toBeFalse();
});

it('denies a location whose assignment is paused at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);
    $michael = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $michael->id]);

    expect($user->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed())->toBeFalse();
});

it('denies a colleague when the shared assignment is paused at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $dwight->locations()->attach($scranton);
    $user = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Location);
    $michael = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $michael->id]);

    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed())->toBeFalse();
});

it('denies an account without a staff record at location scope', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $dwight->locations()->attach($scranton);
    $locationManager = userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);
    $staffEditor = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Location);

    expect($locationManager->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed())->toBeFalse();
    expect($staffEditor->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed())->toBeFalse();
});

it('refuses to check a permission that is not about a location against one', function () {

    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::RoleManage, ScopeEnum::Company);

    $user->permission(PermissionEnum::RoleManage)->forLocation($scranton);
})->throws(InvalidArgumentException::class);

it('says whether a permission is held at any scope', function () {
    $company = Company::factory()->create();
    $user = userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);

    expect($user->permission(PermissionEnum::LocationManage)->anywhere()->allowed())->toBeTrue();
    expect($user->permission(PermissionEnum::ReportView)->anywhere()->allowed())->toBeFalse();
});

it('allows a company wide permission the role grants', function () {
    $company = Company::factory()->create();
    $user = userWith($company, PermissionEnum::CompanyManage, ScopeEnum::Company);

    expect($user->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeTrue();
});

it('denies a company wide permission the role does not grant', function () {
    $company = Company::factory()->create();
    $user = userWith($company, PermissionEnum::StaffCreate, ScopeEnum::Company);

    expect($user->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeFalse();
});

it('denies a company wide permission against another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $user = userWith($dunderMifflin, PermissionEnum::CompanyManage, ScopeEnum::Company);

    expect($user->permission(PermissionEnum::CompanyManage)->forCompany($michaelScottPaperCompany)->allowed())->toBeFalse();
});

it('refuses to check a company wide permission against a staff member', function () {

    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::CompanyManage, ScopeEnum::Company);

    $user->permission(PermissionEnum::CompanyManage)->forStaff($dwight);
})->throws(InvalidArgumentException::class);

it('refuses to check a permission about one staff member against a company', function () {

    $company = Company::factory()->create();
    $user = userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    $user->permission(PermissionEnum::StaffUpdate)->forCompany($company);
})->throws(InvalidArgumentException::class);

it('throws a not found when the check fails', function () {

    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

    $user->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->authorize();
})->throws(ModelNotFoundException::class);

it('says nothing when the check passes', function () {
    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

    $user->permission(PermissionEnum::StaffView)->forStaff($dwight)->authorize();

    expect(true)->toBeTrue();
});

it('reads the roles of a user once a request', function () {
    $company = Company::factory()->create();
    $user = userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);
    $staffMembers = Staff::factory()->count(5)->create(['company_id' => $company->id]);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    foreach ($staffMembers as $staff) {
        $user->permission(PermissionEnum::StaffView)->forStaff($staff)->allowed();
    }

    expect($queries)->toEqual(1);
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
