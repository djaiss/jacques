<?php

declare(strict_types=1);

use App\Models\User;

it('shows the dashboard', function () {
    $michael = User::factory()->create();

    $response = $this->actingAs($michael)->get(route('dashboard.index'));

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});

it('sends a guest to sign in', function () {
    $this->get(route('dashboard.index'))->assertRedirect(route('auth.signIn.new'));
});
