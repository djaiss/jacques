<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\RolePermission;

it('belongs to a role', function () {
    $role = Role::factory()->create();
    $permission = RolePermission::factory()->create(['role_id' => $role->id]);

    expect($permission->role()->exists())->toBeTrue();
    expect($permission->role->id)->toEqual($role->id);
});
