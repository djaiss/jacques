<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $held = UserRole::factory()->create(['user_id' => $user->id]);

    expect($held->user()->exists())->toBeTrue();
    expect($held->user->id)->toEqual($user->id);
});

it('belongs to a role', function () {
    $role = Role::factory()->create();
    $held = UserRole::factory()->create(['role_id' => $role->id]);

    expect($held->role()->exists())->toBeTrue();
    expect($held->role->id)->toEqual($role->id);
});

it('says when the role was handed out', function () {
    $held = UserRole::factory()->create();

    expect($held->created_at)->not->toBeNull();
    expect($held->created_at->timestamp)->toEqualWithDelta(now()->timestamp, 2);
});
