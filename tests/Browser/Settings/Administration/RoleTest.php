<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;

it('creates a role and opens it', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $this->actingAs($user);

    $page = visit(route('settings.roles.index'));

    $page->click('New role')
        ->assertVisible('[role="dialog"][aria-labelledby="create-role-title"]')
        ->type('name', 'Regional Manager')
        ->press('Create the role')
        ->assertSee('The role is created.')
        ->assertValue('#role-name', 'Regional Manager')
        ->assertNoJavaScriptErrors();

    $role = Role::query()->where('name', 'Regional Manager')->firstOrFail();

    expect($role->company_id)->toBe($user->company_id);

    $page->assertPathIs("/settings/administration/roles/{$role->id}");
});

it('keeps the dialog open when the name of the new role is too short', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $this->actingAs($user);

    $page = visit(route('settings.roles.index'));

    $page->click('New role')
        ->type('name', 'D')
        ->press('Create the role')
        ->assertPathIs('/settings/administration/roles')
        ->assertVisible('[role="dialog"][aria-labelledby="create-role-title"]');

    $this->assertDatabaseMissing('roles', ['name' => 'D']);
});

it('duplicates a role', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->press('Duplicate')
        ->assertSee('The role is copied.')
        ->assertValue('#role-name', 'Salesperson (copy)')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('roles', ['company_id' => $user->company_id, 'name' => 'Salesperson (copy)']);

    $page->navigate(route('settings.roles.index'))
        ->assertSee('Salesperson (copy)');
});

it('renames a role', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->fill('#role-name', 'Assistant to the Regional Manager')
        ->press('Save changes')
        ->assertSee('The role is saved.')
        ->assertValue('#role-name', 'Assistant to the Regional Manager')
        ->assertNoJavaScriptErrors();

    expect($role->refresh()->name)->toBe('Assistant to the Regional Manager');

    $page->navigate(route('settings.roles.index'))
        ->assertSee('Assistant to the Regional Manager')
        ->assertDontSee('Salesperson');
});

it('creates a role on a phone', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $this->actingAs($user);

    $page = visit(route('settings.roles.index'))->on()->mobile();

    $page->click('New role')
        ->type('name', 'Warehouse Foreman')
        ->press('Create the role')
        ->assertSee('The role is created.')
        ->assertValue('#role-name', 'Warehouse Foreman')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('roles', ['company_id' => $user->company_id, 'name' => 'Warehouse Foreman']);
});

it('asks before deleting a role and keeps it on cancel', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->click('[aria-label="More about this role"]')
        ->click('#role-menu button:has-text("Delete this role")')
        ->assertSee('Delete Salesperson?')
        ->click('[role="dialog"] button:has-text("Keep it")')
        ->assertDontSee('Delete Salesperson?')
        ->assertPathIs("/settings/administration/roles/{$role->id}");

    $this->assertModelExists($role);

    $page->navigate(route('settings.roles.index'))
        ->assertSee('Salesperson');
});

it('deletes a role once confirmed', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->click('[aria-label="More about this role"]')
        ->click('#role-menu button:has-text("Delete this role")')
        ->assertSee('Delete Salesperson?')
        ->click('[role="dialog"] button:has-text("Delete this role")')
        ->assertPathIs('/settings/administration/roles')
        ->assertSee('The role is deleted.')
        ->assertDontSee('Salesperson')
        ->assertNoJavaScriptErrors();

    $this->assertModelMissing($role);
});

it('does not offer to delete a role somebody holds', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    User::factory()->create(['company_id' => $user->company_id])->roles()->attach($role->id);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', $role->id));

    $page->click('[aria-label="More about this role"]')
        ->assertSee('held by 1')
        ->assertNoJavaScriptErrors();
});

it('refuses somebody who may not manage roles', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesperson']);
    $this->actingAs($user);

    visit(route('settings.roles.show', $role->id))
        ->assertSee('Not Found')
        ->assertDontSee('Salesperson')
        ->assertDontSee('Duplicate');

    $this->assertModelExists($role);
});
