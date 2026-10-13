<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultRoles;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultRolesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_three_roles_a_company_starts_with(): void
    {
        $company = Company::factory()->create();

        $roles = new CreateDefaultRoles(company: $company)->execute();

        $this->assertCount(4, $roles);
        $this->assertEquals(
            [Role::ADMINISTRATOR, Role::PEOPLE_ADMINISTRATOR, Role::IT_ADMINISTRATOR, Role::MEMBER],
            $roles->pluck('slug')->all(),
        );

        foreach ($roles as $role) {
            $this->assertTrue($role->is_default);
            $this->assertTrue($role->is_editable);
            $this->assertEquals($company->id, $role->company_id);
        }
    }

    #[Test]
    public function it_gives_the_administrator_every_permission_at_company_scope(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultRoles(company: $company)->execute();

        $administrator = $company->roles()->where('slug', Role::ADMINISTRATOR)->firstOrFail();

        $this->assertCount(count(PermissionEnum::cases()), $administrator->permissions);

        foreach ($administrator->permissions as $grant) {
            $this->assertEquals(ScopeEnum::Company, $grant->scope);
        }
    }

    #[Test]
    public function it_gives_the_people_administrator_the_employee_permissions_only(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultRoles(company: $company)->execute();

        $role = $company->roles()->where('slug', Role::PEOPLE_ADMINISTRATOR)->firstOrFail();

        $this->assertEquals(
            [
                PermissionEnum::EmployeeCreate->value,
                PermissionEnum::EmployeeUpdate->value,
                PermissionEnum::EmployeeUpdatePrivate->value,
                PermissionEnum::EmployeeView->value,
                PermissionEnum::EmployeeViewPrivate->value,
            ],
            $role->permissions->pluck('permission.value')->sort()->values()->all(),
        );
    }

    #[Test]
    public function it_keeps_a_member_to_their_own_record_apart_from_seeing_colleagues(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultRoles(company: $company)->execute();

        $role = $company->roles()->where('slug', Role::MEMBER)->firstOrFail();
        $scopes = $role->permissions->mapWithKeys(
            fn ($grant): array => [$grant->permission->value => $grant->scope],
        );

        $this->assertEquals(ScopeEnum::Company, $scopes[PermissionEnum::EmployeeView->value]);
        $this->assertEquals(ScopeEnum::Self, $scopes[PermissionEnum::EmployeeUpdate->value]);
        $this->assertEquals(ScopeEnum::Self, $scopes[PermissionEnum::EmployeeViewPrivate->value]);
        $this->assertEquals(ScopeEnum::Self, $scopes[PermissionEnum::EmployeeUpdatePrivate->value]);
        $this->assertCount(4, $role->permissions);
    }
}
