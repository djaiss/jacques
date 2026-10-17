<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use PragmaRX\Google2FA\Google2FA;

it('shows the screen that pairs an authenticator app', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->actingAs($user)->get(route('settings.twoFactor.new'));

    $response->assertStatus(200);
    $response->assertSee('Pair your authenticator app', escape: false);
    $response->assertSee('michael.scott@dundermifflin.com', escape: false);
    $response->assertSee('<svg', escape: false);

    expect($user->refresh()->two_factor_secret)->not->toBeNull();
});

it('carries the top bar and the way back to the security screen', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('settings.twoFactor.new'));

    $response->assertStatus(200);
    $response->assertSee('id="top-bar-menu"', escape: false);
    $response->assertSee('Dunder Mifflin', escape: false);
    $response->assertSee(route('settings.security.index'), escape: false);
});

it('refuses a visitor who is not signed in', function () {
    $response = $this->get(route('settings.twoFactor.new'));

    $response->assertRedirect(route('auth.signIn.new'));
});

it('turns two factor authentication on', function () {
    Queue::fake();

    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)->post(route('settings.twoFactor.create'), [
        'code' => $google2fa->getCurrentOtp($secret),
    ]);

    $response->assertRedirect(route('settings.security.index'));
    $response->assertSessionHas('status', 'Two factor authentication is on.');

    expect($user->refresh()->usesTwoFactorAuthentication())->toBeTrue();
});

it('says so when the code is not right', function () {
    Queue::fake();

    $user = User::factory()->create([
        'two_factor_secret' => new Google2FA()->generateSecretKey(),
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)->post(route('settings.twoFactor.create'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code');

    expect($user->refresh()->usesTwoFactorAuthentication())->toBeFalse();
});

it('asks for a code', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('settings.twoFactor.create'), []);

    $response->assertSessionHasErrors('code');
});

it('turns two factor authentication off', function () {
    Queue::fake();

    $user = User::factory()->twoFactor()->create();

    $response = $this->actingAs($user)->delete(route('settings.twoFactor.destroy'));

    $response->assertRedirect(route('settings.security.index'));
    $response->assertSessionHas('status', 'Two factor authentication is off.');

    $user->refresh();

    expect($user->usesTwoFactorAuthentication())->toBeFalse();
    expect($user->two_factor_secret)->toBeNull();
});
