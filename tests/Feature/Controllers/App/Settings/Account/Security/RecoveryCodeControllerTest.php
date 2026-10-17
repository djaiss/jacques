<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('hands out a new set of recovery codes', function () {
    Queue::fake();

    $user = User::factory()->twoFactor()->create();

    $response = $this->actingAs($user)->post(route('settings.recoveryCodes.create'));

    $response->assertRedirect(route('settings.security.index'));
    $response->assertSessionHas('status', 'Your recovery codes are new.');

    expect($user->refresh()->two_factor_recovery_codes)->toHaveCount(8);
});

it('is not found when two factor authentication is not in use', function () {
    $user = User::factory()->create(['two_factor_confirmed_at' => null]);

    $response = $this->actingAs($user)->post(route('settings.recoveryCodes.create'));

    $response->assertNotFound();
});
