<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('copies a role with everything it grants', function () {
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

    expect($copy->slug)->toEqual('branch-manager-copy');
    expect($copy->description)->toEqual('Runs Scranton.');
    expect($copy->users()->count())->toEqual(0);
    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $copy->id,
        'permission' => PermissionEnum::StaffUpdate->value,
        'scope' => ScopeEnum::Self->value,
    ]);
});

it('copies a role that is not editable', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $original = Role::factory()->locked()->create(['company_id' => $company->id, 'name' => 'Owner']);

    $this->actingAs($user)->post(route('settings.roleDuplicates.create', $original->id));

    $copy = $company->roles()->where('name', 'Owner (copy)')->firstOrFail();

    expect($copy->is_editable)->toBeTrue();
});

it('does not find a role of another company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::RoleManage);

    $elsewhere = Role::factory()->create(['company_id' => Company::factory()->create()->id]);

    $response = $this->actingAs($user)->post(route('settings.roleDuplicates.create', $elsewhere->id));

    $response->assertNotFound();
});

it('refuses somebody who may not administer the company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->giveDefaultRole($user, Role::STAFF);

    $role = Role::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->post(route('settings.roleDuplicates.create', $role->id));

    $response->assertNotFound();
});
