<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Staff;
use App\Models\User;

it('shows the security screen', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'password_changed_at' => now()->subDays(2),
    ]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Dwight',
        'last_name' => 'Schrute',
        'display_name' => null,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('Security and access', escape: false);
    $response->assertSee('Password', escape: false);
    $response->assertSee('Current password', escape: false);
    $response->assertSee('Dwight Schrute', escape: false);
    $response->assertSee('Dunder Mifflin', escape: false);
    $response->assertSee('Last changed 2 days ago', escape: false);
});

it('carries the top bar and the way back to the settings hub', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('id="top-bar-menu"', escape: false);
    $response->assertSee('Dunder Mifflin', escape: false);
    $response->assertSee(route('settings.index'), escape: false);
});

it('says there are no api keys when none have been made', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('API keys', escape: false);
    $response->assertSee('Active · no API keys', escape: false);
    $response->assertSee('No API keys yet', escape: false);
});

it('lists the api keys', function () {
    $user = User::factory()->create();
    $user->createToken('Dundie awards bot');

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('Active · 1 API key', escape: false);
    $response->assertSee('Dundie awards bot', escape: false);
    $response->assertSee('never used', escape: false);
    $response->assertDontSee('No API keys yet', escape: false);
});

it('leaves out the date when the password was never changed', function () {
    $user = User::factory()->create(['password_changed_at' => null]);

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Last changed', escape: false);
});

it('says there is nothing to change when the account signs in elsewhere', function () {
    $user = User::factory()->singleSignOn()->create();

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('You sign in through your identity provider, so there is no password to change here.', escape: false);
    $response->assertDontSee('Current password', escape: false);
});

it('offers to turn two factor authentication on when it is off', function () {
    $user = User::factory()->create(['two_factor_confirmed_at' => null]);

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('Two factor authentication', escape: false);
    $response->assertSee('Authenticator app', escape: false);
    $response->assertSee('Turn it on', escape: false);
    $response->assertDontSee('Recovery codes', escape: false);
});

it('shows the recovery codes when two factor authentication is on', function () {
    $user = User::factory()->twoFactor()->create(['two_factor_confirmed_at' => now()->subDays(3)]);

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('Turned on 3 days ago', escape: false);
    $response->assertSee('Recovery codes', escape: false);
    $response->assertSee('scranton-1', escape: false);
    $response->assertSee('scranton-2', escape: false);
});

it('says so when every recovery code has been used', function () {
    $user = User::factory()->twoFactor()->create(['two_factor_recovery_codes' => []]);

    $response = $this->actingAs($user)->get(route('settings.security.index'));

    $response->assertStatus(200);
    $response->assertSee('You have used every code. Ask for new ones before you need them.', escape: false);
});

it('redirects a visitor who is not signed in', function () {
    $response = $this->get(route('settings.security.index'));

    $response->assertRedirect(route('auth.signIn.new'));
});
