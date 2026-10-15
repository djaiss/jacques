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
use App\Permissions\StaffScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffScopeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_everybody_in_the_company_at_company_scope(): void
    {
        $company = Company::factory()->create();
        Staff::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

        $this->assertEquals(3, StaffScope::for($user, PermissionEnum::StaffView)->count());
    }

    #[Test]
    public function it_returns_only_their_own_record_at_self_scope(): void
    {
        $company = Company::factory()->create();
        $jim = Staff::factory()->create(['company_id' => $company->id]);
        Staff::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self, $jim);

        $staffMembers = StaffScope::for($user, PermissionEnum::StaffUpdate)->get();

        $this->assertCount(1, $staffMembers);
        $this->assertEquals($jim->id, $staffMembers->first()->id);
    }

    #[Test]
    public function it_returns_nobody_when_the_user_has_no_staff_record_at_self_scope(): void
    {
        $company = Company::factory()->create();
        Staff::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffUpdate, ScopeEnum::Self);

        $this->assertEquals(0, StaffScope::for($user, PermissionEnum::StaffUpdate)->count());
    }

    #[Test]
    public function it_returns_nobody_when_no_role_grants_the_permission(): void
    {
        $company = Company::factory()->create();
        Staff::factory()->count(3)->create(['company_id' => $company->id]);
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Company);

        $this->assertEquals(0, StaffScope::for($user, PermissionEnum::StaffUpdate)->count());
    }

    #[Test]
    public function it_never_reaches_into_another_company(): void
    {
        $dunderMifflin = Company::factory()->create();
        Staff::factory()->count(2)->create(['company_id' => $dunderMifflin->id]);

        $michaelScottPaperCompany = Company::factory()->create();
        Staff::factory()->count(4)->create(['company_id' => $michaelScottPaperCompany->id]);

        $user = $this->userWith($dunderMifflin, PermissionEnum::StaffView, ScopeEnum::Company);

        $staffMembers = StaffScope::for($user, PermissionEnum::StaffView)->get();

        $this->assertCount(2, $staffMembers);
        $this->assertEquals([$dunderMifflin->id], $staffMembers->pluck('company_id')->unique()->all());
    }

    #[Test]
    public function it_returns_everybody_in_the_company_for_the_owner_role(): void
    {
        $company = Company::factory()->create();
        Staff::factory()->count(3)->create(['company_id' => $company->id]);
        $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

        $this->assertEquals(3, StaffScope::for($owner, PermissionEnum::StaffView)->count());
    }

    #[Test]
    public function it_returns_the_colleagues_of_their_locations_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        Staff::factory()->create(['company_id' => $company->id]);
        $dwight->locations()->attach($scranton);
        $michael = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Location);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);

        $this->assertEqualsCanonicalizing([$dwight->id, $michael->staff->id], StaffScope::for($michael, PermissionEnum::StaffView)->pluck('id')->all());
    }

    #[Test]
    public function it_returns_nobody_through_a_paused_assignment_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        Staff::factory()->create(['company_id' => $company->id])->locations()->attach($scranton);
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Location);
        $michael = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $michael->id]);

        $this->assertEquals(0, StaffScope::for($user, PermissionEnum::StaffView)->count());
    }

    #[Test]
    public function it_returns_nobody_when_the_user_has_no_staff_record_at_location_scope(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        Staff::factory()->count(2)->create(['company_id' => $company->id])
            ->each(fn (Staff $staff) => $staff->locations()->attach($scranton));
        $user = $this->userWith($company, PermissionEnum::StaffView, ScopeEnum::Location);

        $this->assertEquals(0, StaffScope::for($user, PermissionEnum::StaffView)->count());
    }

    #[Test]
    public function it_refuses_a_permission_that_covers_the_whole_company(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $user = $this->userWith($company, PermissionEnum::RoleManage, ScopeEnum::Company);

        StaffScope::for($user, PermissionEnum::RoleManage);
    }

    #[Test]
    public function it_agrees_with_the_check_made_one_staff_member_at_a_time(): void
    {
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
            $user = $this->userWith($dunderMifflin, PermissionEnum::StaffUpdate, $scope, $pam);
            $pam->locations()->syncWithoutDetaching([$scranton->id]);
            $allowed = StaffScope::for($user, PermissionEnum::StaffUpdate)->pluck('id')->all();

            foreach ($everybody as $staff) {
                $this->assertEquals(
                    $user->permission(PermissionEnum::StaffUpdate)->forStaff($staff)->allowed(),
                    in_array($staff->id, $allowed, true),
                    "The list and the check disagree about staff member {$staff->id} at {$scope->value} scope",
                );
            }
        }
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
