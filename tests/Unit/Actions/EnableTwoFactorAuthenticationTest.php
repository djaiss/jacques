<?php

declare(strict_types=1);

use App\Actions\EnableTwoFactorAuthentication;
use App\Models\User;

it('writes down a secret and draws the square for it', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $enrolment = new EnableTwoFactorAuthentication(user: $user)->execute();

    expect($enrolment['secret'])->not->toBeEmpty();
    expect($user->refresh()->two_factor_secret)->toEqual($enrolment['secret']);

    expect($enrolment['qrCode'])->toStartWith('<svg');
    expect($enrolment['qrCode'])->not->toContain('<?xml');
});

it('leaves the account unprotected until the code is confirmed', function () {
    $user = User::factory()->create();

    new EnableTwoFactorAuthentication(user: $user)->execute();

    $user->refresh();

    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
    expect($user->usesTwoFactorAuthentication())->toBeFalse();
});

it('mints a new secret every time and forgets what came before', function () {
    $user = User::factory()->twoFactor()->create();

    $first = new EnableTwoFactorAuthentication(user: $user)->execute();
    $second = new EnableTwoFactorAuthentication(user: $user)->execute();

    expect($second['secret'])->not->toEqual($first['secret']);
    expect($user->refresh()->two_factor_secret)->toEqual($second['secret']);
    expect($user->two_factor_recovery_codes)->toBeNull();
});
