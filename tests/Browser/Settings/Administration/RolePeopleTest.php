<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;

it('assigns a colleague who then shows on the people tab', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $dwight = User::factory()->create(['company_id' => $user->company_id]);
    Staff::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $dwight->id,
        'display_name' => 'Dwight Schrute',
    ]);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', [$role->id, 'people']));

    $page->assertSee('Nobody holds this role yet')
        ->click('Assign people')
        ->assertVisible('[role="dialog"][aria-labelledby="assign-people-title"]')
        ->click('[role="dialog"] button:has-text("Dwight Schrute")')
        ->assertSee('The role is handed out.')
        ->assertSee('Holds it since')
        ->assertDontSee('Nobody holds this role yet')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('user_roles', ['user_id' => $dwight->id, 'role_id' => $role->id]);
});

it('does not offer somebody from another company', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $dwight = User::factory()->create(['company_id' => $user->company_id]);
    Staff::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $dwight->id,
        'display_name' => 'Dwight Schrute',
    ]);
    $stranger = User::factory()->create(['company_id' => Company::factory()->create(['name' => 'Prince Family Paper'])->id]);
    Staff::factory()->create([
        'company_id' => $stranger->company_id,
        'user_id' => $stranger->id,
        'display_name' => 'Robert California',
    ]);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', [$role->id, 'people']));

    $page->click('Assign people')
        ->assertSee('Dwight Schrute')
        ->assertDontSee('Robert California')
        ->assertNoJavaScriptErrors();
});

it('shows that everybody holds the role when nobody is left to assign', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $user->roles()->attach($role->id);
    $this->grant($user, PermissionEnum::RoleManage);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', [$role->id, 'people']));

    $page->assertSee('Everybody holds it')
        ->assertDontSee('Nobody holds this role yet')
        ->assertNoJavaScriptErrors();
});

it('asks before taking a role back and keeps the holder on cancel', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $michael = User::factory()->create(['company_id' => $user->company_id]);
    Staff::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $michael->id,
        'display_name' => 'Michael Scott',
    ]);
    $michael->roles()->attach($role->id);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', [$role->id, 'people']));

    $page->click('button:has-text("Remove")')
        ->assertSee('Take the role back from Michael Scott?')
        ->click('[role="dialog"][aria-labelledby^="remove-holder"] button:has-text("Keep it")')
        ->assertDontSee('Take the role back from Michael Scott?')
        ->assertSee('Michael Scott')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('user_roles', ['user_id' => $michael->id, 'role_id' => $role->id]);
});

it('takes a role back once confirmed', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $user->company_id, 'name' => 'Salesperson']);
    $michael = User::factory()->create(['company_id' => $user->company_id]);
    Staff::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $michael->id,
        'display_name' => 'Michael Scott',
    ]);
    $michael->roles()->attach($role->id);
    $this->actingAs($user);

    $page = visit(route('settings.roles.show', [$role->id, 'people']));

    $page->click('button:has-text("Remove")')
        ->assertSee('Take the role back from Michael Scott?')
        ->click('[role="dialog"] button:has-text("Take it back")')
        ->assertSee('The role is taken back.')
        ->assertSee('Nobody holds this role yet')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseMissing('user_roles', ['user_id' => $michael->id, 'role_id' => $role->id]);
});
