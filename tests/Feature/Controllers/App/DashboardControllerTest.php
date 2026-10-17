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

it('welcomes somebody who just joined a salon', function () {
    $pam = User::factory()->create();

    $response = $this->actingAs($pam)->withSession(['welcome' => 'Dunder Mifflin'])->get(route('dashboard.index'));

    $response->assertSee('Welcome to Dunder Mifflin!');
});
