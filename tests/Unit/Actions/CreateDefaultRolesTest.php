<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultRoles;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultRolesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_four_roles_a_company_starts_with(): void
    {
        $company = Company::factory()->create();

        $roles = new CreateDefaultRoles(company: $company)->execute();

        $this->assertEquals(
            [Role::OWNER, Role::MANAGER, Role::STAFF, Role::RECEPTIONIST],
            $roles->pluck('slug')->all(),
        );
        $this->assertEquals(
            ['Owner', 'Manager', 'Staff', 'Receptionist'],
            $roles->pluck('name')->all(),
        );

        foreach ($roles as $role) {
            $this->assertTrue($role->is_default);
            $this->assertNotEmpty($role->description);
            $this->assertEquals($company->id, $role->company_id);
        }
    }

    #[Test]
    public function it_locks_the_owner_role_and_nothing_else(): void
    {
        $company = Company::factory()->create();

        $roles = new CreateDefaultRoles(company: $company)->execute();

        $this->assertEquals(
            [Role::OWNER => false, Role::MANAGER => true, Role::STAFF => true, Role::RECEPTIONIST => true],
            $roles->mapWithKeys(fn (Role $role): array => [$role->slug => $role->is_editable])->all(),
        );
    }

    #[Test]
    public function it_names_the_roles_in_the_language_of_whoever_signs_up(): void
    {
        app()->setLocale('fr_FR');
        $company = Company::factory()->create();

        $roles = new CreateDefaultRoles(company: $company)->execute();

        $this->assertEquals('Propriétaire', $roles->firstWhere('slug', Role::OWNER)->name);
    }

    /** @param  array<string, string>  $expected */
    #[Test]
    #[DataProvider('grantsOfEveryDefaultRole')]
    public function it_grants_each_default_role_exactly_what_it_describes(string $slug, array $expected): void
    {
        $company = Company::factory()->create();

        new CreateDefaultRoles(company: $company)->execute();

        $role = $company->roles()->where('slug', $slug)->firstOrFail();

        $this->assertEquals(
            $expected,
            $role->permissions
                ->mapWithKeys(fn ($grant): array => [$grant->permission->value => $grant->scope->value])
                ->sortKeys()
                ->all(),
        );
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function grantsOfEveryDefaultRole(): array
    {
        $owner = collect(PermissionEnum::cases())
            ->mapWithKeys(fn (PermissionEnum $permission): array => [$permission->value => 'company'])
            ->sortKeys()
            ->all();

        return [
            'owner' => [Role::OWNER, $owner],
            'manager' => [Role::MANAGER, [
                'appointment.manage' => 'location',
                'appointment.view' => 'location',
                'client.manage' => 'company',
                'client.view' => 'company',
                'staff.create' => 'location',
                'staff.update' => 'location',
                'staff.view' => 'location',
                'location.manage' => 'location',
                'report.view' => 'location',
                'schedule.manage' => 'location',
                'schedule.view' => 'location',
                'service.manage' => 'location',
                'service.view' => 'location',
            ]],
            'staff' => [Role::STAFF, [
                'appointment.manage' => 'self',
                'appointment.view' => 'self',
                'client.view' => 'self',
                'staff.update' => 'self',
                'staff.view' => 'self',
                'schedule.view' => 'self',
                'service.view' => 'location',
            ]],
            'receptionist' => [Role::RECEPTIONIST, [
                'appointment.manage' => 'location',
                'appointment.view' => 'location',
                'client.manage' => 'company',
                'client.view' => 'company',
                'schedule.view' => 'location',
                'service.view' => 'location',
            ]],
        ];
    }

    #[Test]
    public function it_keeps_the_subscription_and_the_roles_to_the_owner(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultRoles(company: $company)->execute();

        foreach ([Role::MANAGER, Role::STAFF, Role::RECEPTIONIST] as $slug) {
            $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), $slug);

            $this->assertFalse($user->permission(PermissionEnum::BillingManage)->forCompany($company)->allowed());
            $this->assertFalse($user->permission(PermissionEnum::RoleManage)->forCompany($company)->allowed());
            $this->assertFalse($user->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed());
        }
    }

    #[Test]
    public function it_adds_up_staff_and_receptionist_for_somebody_who_holds_both(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $jimAccount = User::factory()->create(['company_id' => $company->id]);
        $jim = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $jimAccount->id]);
        $jim->locations()->attach($scranton);

        $user = User::factory()->create(['company_id' => $company->id]);
        $pam = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $pam->locations()->attach($scranton);
        $this->giveDefaultRole($user, Role::STAFF);
        $this->giveDefaultRole($user, Role::RECEPTIONIST);

        $this->assertTrue($user->permission(PermissionEnum::StaffUpdate)->forStaff($pam)->allowed());
        $this->assertFalse($user->permission(PermissionEnum::StaffUpdate)->forStaff($jim)->allowed());
        $this->assertTrue($user->permission(PermissionEnum::ScheduleView)->forStaff($jim)->allowed());
        $this->assertEquals(
            [ScopeEnum::Self, ScopeEnum::Location],
            $user->grants()[PermissionEnum::AppointmentManage->value],
        );
    }
}
