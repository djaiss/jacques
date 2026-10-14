<?php

declare(strict_types=1);

namespace Tests\Unit\Permissions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\RolePermission;
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
    public function it_allows_an_employee_of_the_company_at_company_scope(): void
    {
        $company = Company::factory()->create();
        $dwight = Employee::factory()->create(['company_id' => $company->id]);
        $michael = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Company);

        $this->assertTrue(
            $michael->permission(PermissionEnum::EmployeeUpdate)->forEmployee($dwight)->allowed(),
        );
    }

    #[Test]
    public function it_allows_only_their_own_record_at_self_scope(): void
    {
        $company = Company::factory()->create();
        $jim = Employee::factory()->create(['company_id' => $company->id]);
        $pam = Employee::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Self, $jim);

        $this->assertTrue(
            $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($jim)->allowed(),
        );
        $this->assertFalse(
            $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($pam)->allowed(),
        );
    }

    #[Test]
    public function it_never_matches_self_when_the_user_has_no_employee(): void
    {
        $company = Company::factory()->create();
        $creed = Employee::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        $this->assertNull($user->employee_id);
        $this->assertFalse(
            $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($creed)->allowed(),
        );
    }

    #[Test]
    public function it_denies_when_no_role_grants_the_permission(): void
    {
        $company = Company::factory()->create();
        $dwight = Employee::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeView, ScopeEnum::Company);

        $this->assertFalse(
            $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($dwight)->allowed(),
        );
    }

    #[Test]
    public function it_denies_an_employee_of_another_company(): void
    {
        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Employee::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $user = $this->userWith($dunderMifflin, PermissionEnum::EmployeeUpdate, ScopeEnum::Company);

        $this->assertFalse(
            $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($stranger)->allowed(),
        );
    }

    #[Test]
    public function it_allows_the_owner_role_everything_inside_their_own_company(): void
    {
        $company = Company::factory()->create();
        $dwight = Employee::factory()->create(['company_id' => $company->id]);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

        $this->assertTrue(
            $owner->permission(PermissionEnum::EmployeeUpdate)->forEmployee($dwight)->allowed(),
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
        $stranger = Employee::factory()->create(['company_id' => $michaelScottPaperCompany->id]);

        $this->assertFalse(
            $owner->permission(PermissionEnum::EmployeeView)->forEmployee($stranger)->allowed(),
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
        $user->locations()->attach($scranton);

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
        $user->locations()->attach($nashua);

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
        $dwight = Employee::factory()->create(['company_id' => $company->id]);
        $andy = Employee::factory()->create(['company_id' => $company->id]);
        User::factory()->create(['company_id' => $company->id, 'employee_id' => $dwight->id])->locations()->attach($scranton);
        User::factory()->create(['company_id' => $company->id, 'employee_id' => $andy->id])->locations()->attach($stamford);
        $michael = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Location);
        $michael->locations()->attach($scranton);

        $this->assertTrue(
            $michael->permission(PermissionEnum::EmployeeUpdate)->forEmployee($dwight)->allowed(),
        );
        $this->assertFalse(
            $michael->permission(PermissionEnum::EmployeeUpdate)->forEmployee($andy)->allowed(),
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
        $angela = Employee::factory()->create(['company_id' => $company->id]);
        $oscar = Employee::factory()->create(['company_id' => $company->id]);

        $user = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Self, $angela);

        $accountant = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create([
            'role_id' => $accountant->id,
            'permission' => PermissionEnum::EmployeeUpdate,
            'scope' => ScopeEnum::Company,
        ]);
        $user->roles()->attach($accountant->id);

        $this->assertTrue(
            $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($oscar)->allowed(),
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
        $user = $this->userWith($company, PermissionEnum::EmployeeCreate, ScopeEnum::Company);

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
    public function it_refuses_to_check_a_company_wide_permission_against_an_employee(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $dwight = Employee::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::CompanyManage, ScopeEnum::Company);

        $user->permission(PermissionEnum::CompanyManage)->forEmployee($dwight);
    }

    #[Test]
    public function it_refuses_to_check_a_permission_about_one_employee_against_a_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::EmployeeUpdate, ScopeEnum::Company);

        $user->permission(PermissionEnum::EmployeeUpdate)->forCompany($company);
    }

    #[Test]
    public function it_throws_a_not_found_when_the_check_fails(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $dwight = Employee::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeView, ScopeEnum::Company);

        $user->permission(PermissionEnum::EmployeeUpdate)->forEmployee($dwight)->authorize();
    }

    #[Test]
    public function it_says_nothing_when_the_check_passes(): void
    {
        $company = Company::factory()->create();
        $dwight = Employee::factory()->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::EmployeeView, ScopeEnum::Company);

        $user->permission(PermissionEnum::EmployeeView)->forEmployee($dwight)->authorize();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_reads_the_roles_of_a_user_once_a_request(): void
    {
        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::EmployeeView, ScopeEnum::Company);
        $employees = Employee::factory()->count(5)->create(['company_id' => $company->id]);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        foreach ($employees as $employee) {
            $user->permission(PermissionEnum::EmployeeView)->forEmployee($employee)->allowed();
        }

        $this->assertEquals(1, $queries);
    }

    private function userWith(Company $company, PermissionEnum $permission, ScopeEnum $scope, ?Employee $employee = null): User
    {
        $role = Role::factory()->create(['company_id' => $company->id]);

        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => $permission,
            'scope' => $scope,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee?->id,
        ]);

        $user->roles()->attach($role->id);

        return $user;
    }
}
