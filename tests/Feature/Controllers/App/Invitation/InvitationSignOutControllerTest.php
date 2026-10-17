<?php

declare(strict_types=1);

use App\Models\User;

it('signs out and returns to the invitation', function () {
    $token = str_repeat('b', 64);
    $michael = User::factory()->create();

    $response = $this->actingAs($michael)->post(route('invitations.signOut.create', ['token' => $token]));

    $response->assertRedirect(route('invitations.show', ['token' => $token]));
    $this->assertGuest();
});
