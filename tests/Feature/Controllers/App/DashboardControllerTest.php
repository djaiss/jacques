<?php

declare(strict_types=1);

use App\Models\Role;
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

it('offers the parts of the salon in the menu', function () {
    $michael = test()->giveDefaultRole(User::factory()->create(), Role::OWNER);

    $response = $this->actingAs($michael)->get(route('dashboard.index'));

    $response->assertSeeInOrder(['Dashboard', 'Clients', 'Services', 'Team']);
    $response->assertSeeInOrder(['Profile', 'Logs', 'Security and access', 'Preferences']);
    $response->assertSee('Type to go to a client, a service, or a person…');
    $response->assertSee('href="'.route('dashboard.index').'"', escape: false);
    $response->assertSee('href="'.route('settings.team.index').'"', escape: false);
    $response->assertSee('href="'.route('services.index').'"', escape: false);
    $response->assertDontSee('Licences');
    $response->assertDontSee('Software');
});

it('draws the parts without a screen as unavailable', function () {
    $dwight = User::factory()->create();

    $response = $this->actingAs($dwight)->get(route('dashboard.index'));

    $response->assertSee('Team');
    $response->assertDontSee('href="'.route('settings.team.index').'"', escape: false);
    $response->assertDontSee('href="'.route('services.index').'"', escape: false);
    expect(substr_count($response->getContent(), 'aria-disabled="true"'))->toBe(3);
});
