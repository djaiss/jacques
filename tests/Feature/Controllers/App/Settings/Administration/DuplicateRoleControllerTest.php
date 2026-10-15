<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DuplicateRoleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_copies_a_role_with_everything_it_grants(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $original = Role::factory()->create(['company_id' => $company->id, 'name' => 'Branch manager', 'description' => 'Runs Scranton.']);
        RolePermission::factory()->create([
            'role_id' => $original->id,
            'permission' => PermissionEnum::StaffUpdate,
            'scope' => ScopeEnum::Self,
        ]);

        $response = $this->actingAs($user)->post(route('settings.roleDuplicates.create', $original->id));

        $copy = $company->roles()->where('name', 'Branch manager (copy)')->firstOrFail();

        $response->assertRedirect(route('settings.roles.show', $copy->id));
        $response->assertSessionHas('status', 'The role is copied.');

        $this->assertEquals('branch-manager-copy', $copy->slug);
        $this->assertEquals('Runs Scranton.', $copy->description);
        $this->assertEquals(0, $copy->users()->count());
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $copy->id,
            'permission' => PermissionEnum::StaffUpdate->value,
            'scope' => ScopeEnum::Self->value,
        ]);
    }

    #[Test]
    public function it_copies_a_role_that_is_not_editable(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $original = Role::factory()->locked()->create(['company_id' => $company->id, 'name' => 'Owner']);

        $this->actingAs($user)->post(route('settings.roleDuplicates.create', $original->id));

        $copy = $company->roles()->where('name', 'Owner (copy)')->firstOrFail();

        $this->assertTrue($copy->is_editable);
    }

    #[Test]
    public function it_does_not_find_a_role_of_another_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $elsewhere = Role::factory()->create(['company_id' => Company::factory()->create()->id]);

        $response = $this->actingAs($user)->post(route('settings.roleDuplicates.create', $elsewhere->id));

        $response->assertNotFound();
    }

    #[Test]
    public function it_refuses_somebody_who_may_not_administer_the_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->giveDefaultRole($user, Role::STAFF);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('settings.roleDuplicates.create', $role->id));

        $response->assertNotFound();
    }
}
