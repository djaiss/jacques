<?php

declare(strict_types=1);

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

it('shows the challenge to somebody whose password was accepted', function () {
    $user = User::factory()->twoFactor()->create();

    $response = $this->withSession(['twoFactor.user.id' => $user->id])
        ->get(route('auth.twoFactor.new'));

    $response->assertStatus(200);
    $response->assertSee('One more step', escape: false);
});

it('sends somebody back to the sign in screen when no password was accepted', function () {
    $response = $this->get(route('auth.twoFactor.new'));

    $response->assertRedirect(route('auth.signIn.new'));
});

it('signs somebody in when the code is right', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();
    $user = User::factory()->twoFactor($secret)->create();

    $response = $this->withSession(['twoFactor.user.id' => $user->id])
        ->post(route('auth.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

    $response->assertRedirect(route('home.index'));
    $this->assertAuthenticatedAs($user);
    expect(session('twoFactor.user.id'))->toBeNull();
});

it('signs somebody in with a recovery code and spends it', function () {
    $user = User::factory()->twoFactor()->create();

    $response = $this->withSession(['twoFactor.user.id' => $user->id])
        ->post(route('auth.twoFactor.create'), ['code' => 'scranton-1']);

    $response->assertRedirect(route('home.index'));
    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->two_factor_recovery_codes)->toEqual(['scranton-2']);
});

it('refuses a code that is not right', function () {
    $user = User::factory()->twoFactor()->create();

    $response = $this->withSession(['twoFactor.user.id' => $user->id])
        ->post(route('auth.twoFactor.create'), ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    $this->assertGuest();
});
