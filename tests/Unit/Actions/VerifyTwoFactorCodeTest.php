<?php

declare(strict_types=1);

use App\Actions\VerifyTwoFactorCode;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

it('accepts the code the authenticator app is showing', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();
    $user = User::factory()->twoFactor($secret)->create();

    $verified = new VerifyTwoFactorCode(
        user: $user,
        code: $google2fa->getCurrentOtp($secret),
    )->execute();

    expect($verified)->toBeTrue();
});

it('refuses a code that is not right', function () {
    $user = User::factory()->twoFactor()->create();

    $verified = new VerifyTwoFactorCode(
        user: $user,
        code: '000000',
    )->execute();

    expect($verified)->toBeFalse();
});

it('accepts a recovery code and spends it', function () {
    $user = User::factory()->twoFactor()->create();

    $verified = new VerifyTwoFactorCode(
        user: $user,
        code: 'scranton-1',
    )->execute();

    expect($verified)->toBeTrue();
    expect($user->refresh()->two_factor_recovery_codes)->toEqual(['scranton-2']);
});

it('refuses a recovery code that was already spent', function () {
    $user = User::factory()->twoFactor()->create();

    new VerifyTwoFactorCode(user: $user, code: 'scranton-1')->execute();

    $verified = new VerifyTwoFactorCode(
        user: $user->refresh(),
        code: 'scranton-1',
    )->execute();

    expect($verified)->toBeFalse();
});

it('refuses anybody who never enrolled', function () {
    $user = User::factory()->create();

    $verified = new VerifyTwoFactorCode(
        user: $user,
        code: '000000',
    )->execute();

    expect($verified)->toBeFalse();
});
