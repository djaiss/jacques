<?php

declare(strict_types=1);

namespace Tests\Unit\Permissions;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingPermissionCheckTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_a_staff_member_of_the_company_at_company_scope(): void
    {
        $company = Company::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $michael = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Company);

        $this->assertTrue(
            $michael->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed(),
        );
    }

    #[Test]
    public function it_allows_only_their_own_record_at_self_scope(): void
    {
        $company = Company::factory()->create();
        $jim = Staff::factory()->create(['company_id' => $company->id]);
        $pam = Staff::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self, $jim);

        $this->assertTrue(
            $user->permission(PermissionEnum::StaffUpdate)->forStaff($jim)->allowed(),
        );
        $this->assertFalse(
            $user->permission(PermissionEnum::StaffUpdate)->forStaff($pam)->allowed(),
        );
    }

    #[Test]
    public function it_never_matches_self_when_the_user_has_no_staff_record(): void
    {
        $company = Company::factory()->create();
        $creed = Staff::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self);

        $this->assertNull($user->staff);
        $this->assertFalse(
            $user->permission(PermissionEnum::StaffUpdate)->forStaff($creed)->allowed(),
        );
    }

    #[Test]
    public function it_denies_when_no_role_grants_the_permission(): void
    {
        $company = Company::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

        $this->assertFalse(
            $user->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed(),
        );
    }

    #[Test]
    public function it_denies_a_staff_member_of_another_company(): void
    {
        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $user = $this->userWith($dunderMifflin, PermissionEnum::StaffUpdate, ScopeEnum::Company);

        $this->assertFalse(
            $user->permission(PermissionEnum::StaffUpdate)->forStaff($stranger)->allowed(),
        );
    }

    #[Test]
    public function it_allows_the_owner_role_everything_inside_their_own_company(): void
    {
        $company = Company::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

        $this->assertTrue(
            $owner->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed(),
        );
        $this->assertTrue(
            $owner->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed(),
        );
        $this->assertTrue(
            $owner->permission(PermissionEnum::BillingManage)->forCompany($company)->allowed(),
        );
    }

    #[Test]
    public function it_denies_the_owner_role_outside_their_own_company(): void
    {
        $dunderMifflin = Company::factory()->create();
        $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $dunderMifflin->id]), Role::OWNER);

        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);

        $this->assertFalse(
            $owner->permission(PermissionEnum::StaffView)->forStaff($stranger)->allowed(),
        );
        $this->assertFalse(
            $owner->permission(PermissionEnum::CompanyManage)->forCompany($michaelScottPaperCompany)->allowed(),
        );
    }

    #[Test]
    public function it_allows_a_location_the_user_is_assigned_to_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id])->locations()->attach($scranton);

        $this->assertTrue(
            $user->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed(),
        );
        $this->assertFalse(
            $user->permission(PermissionEnum::LocationManage)->forLocation($stamford)->allowed(),
        );
    }

    #[Test]
    public function it_allows_every_location_of_the_company_at_company_scope(): void
    {
        $company = Company::factory()->create();
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::LocationManage, ScopeEnum::Company);

        $this->assertTrue(
            $user->permission(PermissionEnum::LocationManage)->forLocation($stamford)->allowed(),
        );
    }

    #[Test]
    public function it_denies_a_location_of_another_company(): void
    {
        $company = Company::factory()->create();
        $nashua = Location::factory()->create();
        $user = $this->userWith($company, PermissionEnum::LocationManage, ScopeEnum::Company);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id])->locations()->attach($nashua);

        $this->assertFalse(
            $user->permission(PermissionEnum::LocationManage)->forLocation($nashua)->allowed(),
        );
    }

    #[Test]
    public function it_allows_a_colleague_who_shares_a_location_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $andy = Staff::factory()->create(['company_id' => $company->id]);
        $dwight->locations()->attach($scranton);
        $andy->locations()->attach($stamford);
        $michael = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Location);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);

        $this->assertTrue(
            $michael->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed(),
        );
        $this->assertFalse(
            $michael->permission(PermissionEnum::StaffUpdate)->forStaff($andy)->allowed(),
        );
    }

    #[Test]
    public function it_denies_a_location_whose_assignment_is_paused_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);
        $michael = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $michael->id]);

        $this->assertFalse(
            $user->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed(),
        );
    }

    #[Test]
    public function it_denies_a_colleague_when_the_shared_assignment_is_paused_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $dwight->locations()->attach($scranton);
        $user = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Location);
        $michael = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $michael->id]);

        $this->assertFalse(
            $user->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed(),
        );
    }

    #[Test]
    public function it_denies_an_account_without_a_staff_record_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $dwight->locations()->attach($scranton);
        $locationManager = $this->userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);
        $staffEditor = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Location);

        $this->assertFalse(
            $locationManager->permission(PermissionEnum::LocationManage)->forLocation($scranton)->allowed(),
        );
        $this->assertFalse(
            $staffEditor->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->allowed(),
        );
    }

    #[Test]
    public function it_refuses_to_check_a_permission_that_is_not_about_a_location_against_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::RoleManage, ScopeEnum::Company);

        $user->permission(PermissionEnum::RoleManage)->forLocation($scranton);
    }

    #[Test]
    public function it_says_whether_a_permission_is_held_at_any_scope(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::LocationManage, ScopeEnum::Location);

        $this->assertTrue($user->permission(PermissionEnum::LocationManage)->anywhere()->allowed());
        $this->assertFalse($user->permission(PermissionEnum::ReportView)->anywhere()->allowed());
    }

    #[Test]
    public function it_adds_up_the_grants_of_several_roles(): void
    {
        $company = Company::factory()->create();
        $angela = Staff::factory()->create(['company_id' => $company->id]);
        $oscar = Staff::factory()->create(['company_id' => $company->id]);

        $user = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self, $angela);

        $accountant = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create([
            'role_id' => $accountant->id,
            'permission' => PermissionEnum::StaffUpdate,
            'scope' => ScopeEnum::Company,
        ]);
        $user->roles()->attach($accountant->id);

        $this->assertTrue(
            $user->permission(PermissionEnum::StaffUpdate)->forStaff($oscar)->allowed(),
        );
    }

    #[Test]
    public function it_allows_a_company_wide_permission_the_role_grants(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::CompanyManage, ScopeEnum::Company);

        $this->assertTrue(
            $user->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed(),
        );
    }

    #[Test]
    public function it_denies_a_company_wide_permission_the_role_does_not_grant(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::StaffCreate, ScopeEnum::Company);

        $this->assertFalse(
            $user->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed(),
        );
    }

    #[Test]
    public function it_denies_a_company_wide_permission_against_another_company(): void
    {
        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $user = $this->userWith($dunderMifflin, PermissionEnum::CompanyManage, ScopeEnum::Company);

        $this->assertFalse(
            $user->permission(PermissionEnum::CompanyManage)->forCompany($michaelScottPaperCompany)->allowed(),
        );
    }

    #[Test]
    public function it_refuses_to_check_a_company_wide_permission_against_a_staff_member(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::CompanyManage, ScopeEnum::Company);

        $user->permission(PermissionEnum::CompanyManage)->forStaff($dwight);
    }

    #[Test]
    public function it_refuses_to_check_a_permission_about_one_staff_member_against_a_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Company);

        $user->permission(PermissionEnum::StaffUpdate)->forCompany($company);
    }

    #[Test]
    public function it_throws_a_not_found_when_the_check_fails(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

        $user->permission(PermissionEnum::StaffUpdate)->forStaff($dwight)->authorize();
    }

    #[Test]
    public function it_says_nothing_when_the_check_passes(): void
    {
        $company = Company::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

        $user->permission(PermissionEnum::StaffView)->forStaff($dwight)->authorize();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_reads_the_roles_of_a_user_once_a_request(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);
        $staffMembers = Staff::factory()->count(5)->create(['company_id' => $company->id]);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        foreach ($staffMembers as $staff) {
            $user->permission(PermissionEnum::StaffView)->forStaff($staff)->allowed();
        }

        $this->assertEquals(1, $queries);
    }

    private function userWith(Company $company, PermissionEnum $permission, ScopeEnum $scope, ?Staff $staff = null): User
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
}
