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

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_roles_of_the_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Regional manager']);
        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => PermissionEnum::StaffView,
            'scope' => ScopeEnum::Company,
        ]);

        $response = $this->actingAs($user)->get(route('settings.roles.index'));

        $response->assertOk();
        $response->assertSee('Regional manager');
        $response->assertSee('Team');
        $response->assertSee('1 of 17 permissions');
    }

    #[Test]
    public function it_shows_one_role(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Assistant to the regional manager']);

        $response = $this->actingAs($user)->get(route('settings.roles.show', $role->id));

        $response->assertOk();
        $response->assertSee('Assistant to the regional manager');
        $response->assertSee('See the profile of a colleague');
    }

    #[Test]
    public function it_shows_who_holds_a_role_on_a_tab_of_its_own(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Assistant to the regional manager']);

        $response = $this->actingAs($user)->get(route('settings.roles.show', [$role->id, 'people']));

        $response->assertOk();
        $response->assertSee('Held by');
        $response->assertDontSee('See the profile of a colleague');
    }

    #[Test]
    public function it_hides_the_screen_from_somebody_who_may_not_administer_the_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->giveDefaultRole($user, Role::STAFF);

        $response = $this->actingAs($user)->get(route('settings.roles.index'));

        $response->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_role_of_another_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => Company::factory()->create()->id]);

        $response = $this->actingAs($user)->get(route('settings.roles.show', $role->id));

        $response->assertNotFound();
    }

    #[Test]
    public function it_renames_a_role_and_says_afresh_what_it_grants(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Temp']);
        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => PermissionEnum::StaffCreate,
            'scope' => ScopeEnum::Company,
        ]);

        $response = $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
            'name' => 'Sales representative',
            'description' => 'Sells paper.',
            'permissions' => [
                PermissionEnum::StaffView->value => ['granted' => '1', 'scope' => ScopeEnum::Self->value],
                PermissionEnum::AppointmentManage->value => ['granted' => '1', 'scope' => ScopeEnum::Location->value],
            ],
        ]);

        $response->assertRedirect(route('settings.roles.show', $role->id));
        $response->assertSessionHas('status', 'The role is saved.');

        $role->refresh();

        $this->assertEquals('Sales representative', $role->name);
        $this->assertEquals('Sells paper.', $role->description);
        $this->assertEquals(2, $role->permissions()->count());
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission' => PermissionEnum::AppointmentManage->value,
            'scope' => ScopeEnum::Location->value,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission' => PermissionEnum::StaffView->value,
            'scope' => ScopeEnum::Self->value,
        ]);
    }

    #[Test]
    public function it_ignores_the_scope_of_a_permission_that_covers_the_whole_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
            'name' => 'Corporate',
            'permissions' => [
                PermissionEnum::CompanyManage->value => ['granted' => '1', 'scope' => ScopeEnum::Self->value],
            ],
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission' => PermissionEnum::CompanyManage->value,
            'scope' => ScopeEnum::Company->value,
        ]);
    }

    #[Test]
    public function it_falls_back_to_the_company_for_a_scope_the_permission_does_not_offer(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
            'name' => 'Reception',
            'permissions' => [
                PermissionEnum::ClientView->value => ['granted' => '1', 'scope' => ScopeEnum::Location->value],
            ],
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission' => PermissionEnum::ClientView->value,
            'scope' => ScopeEnum::Company->value,
        ]);
    }

    #[Test]
    public function it_ignores_a_permission_that_does_not_exist(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
            'name' => 'Corporate',
            'permissions' => [
                'warehouse.forklift' => ['granted' => '1', 'scope' => ScopeEnum::Company->value],
            ],
        ]);

        $this->assertEquals(0, $role->permissions()->count());
    }

    #[Test]
    public function it_refuses_to_change_a_role_that_is_not_editable(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->locked()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
            'name' => 'Anything',
        ]);

        $response->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_name_that_is_too_short(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
            'name' => 'a',
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_counts_the_permissions_just_submitted_rather_than_the_ones_saved(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->withSession([
            '_old_input' => [
                'permissions' => [
                    PermissionEnum::StaffView->value => ['granted' => '1'],
                    PermissionEnum::StaffUpdate->value => ['granted' => '1'],
                ],
            ],
        ])->get(route('settings.roles.show', $role->id));

        $response->assertOk();
        $response->assertSee('2 of '.count(PermissionEnum::cases()).' granted');
        $response->assertSee('Permissions · 2');
    }

    #[Test]
    public function it_refuses_a_scope_that_is_not_one_of_ours(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
            'name' => 'Sales representative',
            'permissions' => [
                PermissionEnum::StaffView->value => ['granted' => '1', 'scope' => 'reports'],
            ],
        ]);

        $response->assertSessionHasErrors('permissions.staff.view.scope');
    }

    #[Test]
    public function it_creates_a_role_that_grants_nothing(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $response = $this->actingAs($user)->post(route('settings.roles.create'), [
            'name' => 'Regional people lead',
        ]);

        $role = $company->roles()->where('name', 'Regional people lead')->firstOrFail();

        $response->assertRedirect(route('settings.roles.show', $role->id));
        $response->assertSessionHas('status', 'The role is created.');

        $this->assertEquals('regional-people-lead', $role->slug);
        $this->assertEquals(0, $role->permissions()->count());
    }

    #[Test]
    public function it_deletes_a_role_nobody_holds(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(route('settings.roles.destroy', $role->id));

        $response->assertRedirect(route('settings.roles.index'));
        $response->assertSessionHas('status', 'The role is deleted.');
        $this->assertModelMissing($role);
    }

    #[Test]
    public function it_refuses_to_delete_a_role_somebody_holds(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::RoleManage);

        $role = Role::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->delete(route('settings.roles.destroy', $role->id));

        $response->assertNotFound();
        $this->assertModelExists($role);
    }

    #[Test]
    public function it_refuses_everything_to_somebody_who_may_not_administer_the_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->giveDefaultRole($user, Role::STAFF);

        $role = Role::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('settings.roles.create'), ['name' => 'Anything'])->assertNotFound();
        $this->actingAs($user)->put(route('settings.roles.update', $role->id), ['name' => 'Anything'])->assertNotFound();
        $this->actingAs($user)->delete(route('settings.roles.destroy', $role->id))->assertNotFound();
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.roles.index'));

        $response->assertRedirect(route('auth.signIn.new'));
    }
}
