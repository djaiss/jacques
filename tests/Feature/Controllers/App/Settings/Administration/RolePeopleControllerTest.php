<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('hands a role to somebody', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $colleague = User::factory()->create(['company_id' => $company->id, 'email' => 'dwight@dundermifflin.com']);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), [
        'user' => $colleague->id,
    ]);

    $response->assertRedirect(route('settings.roles.show', [$role->id, 'people']));
    $response->assertSessionHas('status', 'The role is handed out.');
    $this->assertDatabaseHas('user_roles', ['user_id' => $colleague->id, 'role_id' => $role->id]);
});

it('takes a role back', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $colleague = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $colleague->roles()->attach($role->id);

    $response = $this->actingAs($user)->delete(route('settings.rolePeople.destroy', [$role->id, $colleague->id]));

    $response->assertRedirect(route('settings.roles.show', [$role->id, 'people']));
    $response->assertSessionHas('status', 'The role is taken back.');
    $this->assertDatabaseMissing('user_roles', ['user_id' => $colleague->id, 'role_id' => $role->id]);
});

it('shows who holds the role', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $colleague = User::factory()->create(['company_id' => $company->id, 'email' => 'pam@dundermifflin.com']);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $colleague->roles()->attach($role->id);

    $response = $this->actingAs($user)->get(route('settings.roles.show', [$role->id, 'people']));

    $response->assertOk();
    $response->assertSee('pam@dundermifflin.com');
    $response->assertSee('They keep whatever their other roles grant. Anything only this role gave them, they lose.');
});

it('does not find somebody from another company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $stranger = User::factory()->create(['company_id' => Company::factory()->create()->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), [
        'user' => $stranger->id,
    ]);

    $response->assertNotFound();
});

it('refuses somebody who may not administer the company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $colleague = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), [
        'user' => $colleague->id,
    ]);

    $response->assertNotFound();
});

it('refuses a request that names nobody', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $role = Role::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->post(route('settings.rolePeople.create', $role->id), []);

    $response->assertSessionHasErrors('user');
});
