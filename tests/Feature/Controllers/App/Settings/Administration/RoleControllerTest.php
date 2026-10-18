<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows the roles of the company', function () {
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
});

it('shows one role', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Assistant to the regional manager']);

    $response = $this->actingAs($user)->get(route('settings.roles.show', $role->id));

    $response->assertOk();
    $response->assertSee('Assistant to the regional manager');
    $response->assertSee('See the profile of a colleague');
});

it('shows who holds a role on a tab of its own', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Assistant to the regional manager']);

    $response = $this->actingAs($user)->get(route('settings.roles.show', [$role->id, 'people']));

    $response->assertOk();
    $response->assertSee('Held by');
    $response->assertDontSee('See the profile of a colleague');
});

it('hides the screen from somebody who may not administer the company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $response = $this->actingAs($user)->get(route('settings.roles.index'));

    $response->assertNotFound();
});

it('does not find a role of another company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->create(['company_id' => Company::factory()->create()->id]);

    $response = $this->actingAs($user)->get(route('settings.roles.show', $role->id));

    $response->assertNotFound();
});

it('renames a role and says afresh what it grants', function () {
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

    expect($role->name)->toEqual('Sales representative');
    expect($role->description)->toEqual('Sells paper.');
    expect($role->permissions()->count())->toEqual(2);
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
});

it('ignores the scope of a permission that covers the whole company', function () {
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
});

it('falls back to the company for a scope the permission does not offer', function () {
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
});

it('ignores a permission that does not exist', function () {
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

    expect($role->permissions()->count())->toEqual(0);
});

it('refuses to change a role that is not editable', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->locked()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
        'name' => 'Anything',
    ]);

    $response->assertNotFound();
});

it('refuses a name that is too short', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->put(route('settings.roles.update', $role->id), [
        'name' => 'a',
    ]);

    $response->assertSessionHasErrors('name');
});

it('counts the permissions just submitted rather than the ones saved', function () {
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
});

it('refuses a scope that is not one of ours', function () {
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
});

it('creates a role that grants nothing', function () {
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

    expect($role->slug)->toEqual('regional-people-lead');
    expect($role->permissions()->count())->toEqual(0);
});

it('deletes a role nobody holds', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->delete(route('settings.roles.destroy', $role->id));

    $response->assertRedirect(route('settings.roles.index'));
    $response->assertSessionHas('status', 'The role is deleted.');
    $this->assertModelMissing($role);
});

it('refuses to delete a role somebody holds', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->create(['company_id' => $company->id]);
    User::factory()->create(['company_id' => $company->id])->roles()->attach($role->id);

    $response = $this->actingAs($user)->delete(route('settings.roles.destroy', $role->id));

    $response->assertNotFound();
    $this->assertModelExists($role);
});

it('refuses everything to somebody who may not administer the company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $role = Role::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)->post(route('settings.roles.create'), ['name' => 'Anything'])->assertNotFound();
    $this->actingAs($user)->put(route('settings.roles.update', $role->id), ['name' => 'Anything'])->assertNotFound();
    $this->actingAs($user)->delete(route('settings.roles.destroy', $role->id))->assertNotFound();
});

it('redirects a visitor who is not signed in', function () {
    $response = $this->get(route('settings.roles.index'));

    $response->assertRedirect(route('auth.signIn.new'));
});
