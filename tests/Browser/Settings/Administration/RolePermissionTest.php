<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Role;
use App\Models\User;

it('narrows the permissions to what the filter matches', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->assertSee('Add and change clients')
        ->assertSee('Change the services')
        ->type('Filter permissions', 'clients')
        ->assertSee('Add and change clients')
        ->assertDontSee('Change the services')
        ->assertNoJavaScriptErrors();
});

it('says so when no permission matches the filter', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->assertDontSee('No permission matches that.')
        ->type('Filter permissions', 'Scranton Strangler')
        ->assertSee('No permission matches that.')
        ->assertDontSee('Add and change clients')
        ->assertNoJavaScriptErrors();
});

it('grants a permission with a scope and keeps it after a reload', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->click('Change the services')
        ->click('label:has(input[name="permissions[service.manage][scope]"][value="location"])')
        ->press('Save changes')
        ->assertSee('The role is saved.')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $role->id,
        'permission' => PermissionEnum::ServiceManage->value,
        'scope' => ScopeEnum::Location->value,
    ]);
    $this->assertDatabaseCount('role_permissions', 2);

    $page->navigate(route('settings.roles.show', $role->id))
        ->assertChecked('input[name="permissions[service.manage][granted]"]')
        ->assertChecked('input[name="permissions[service.manage][scope]"][value="location"]')
        ->assertSee('1 of 17 granted');
});

it('takes a permission back', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $role->permissions()->create(['permission' => PermissionEnum::ClientView, 'scope' => ScopeEnum::Company]);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->click('See a client')
        ->press('Save changes')
        ->assertSee('The role is saved.')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id]);
});
