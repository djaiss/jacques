<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;

it('belongs to a company', function () {
    $company = Company::factory()->create();
    $role = Role::factory()->create(['company_id' => $company->id]);

    expect($role->company()->exists())->toBeTrue();
    expect($role->company->id)->toEqual($company->id);
});

it('has many permissions', function () {
    $role = Role::factory()->create();
    RolePermission::factory()->create(['role_id' => $role->id]);

    expect($role->permissions()->exists())->toBeTrue();
});

it('has many users', function () {
    $company = Company::factory()->create();
    $role = Role::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create(['company_id' => $company->id]);

    $user->roles()->attach($role->id);

    expect($role->users()->exists())->toBeTrue();
    expect($role->users()->first()->id)->toEqual($user->id);
});
