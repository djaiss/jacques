<?php

declare(strict_types=1);

use App\Actions\ConfirmTwoFactorAuthentication;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use PragmaRX\Google2FA\Google2FA;

it('turns two factor authentication on and hands out recovery codes', function () {
    Queue::fake();

    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    $confirmed = new ConfirmTwoFactorAuthentication(
        user: $user,
        code: $google2fa->getCurrentOtp($secret),
    )->execute();

    expect($confirmed)->toBeTrue();

    $user->refresh();

    expect($user->two_factor_confirmed_at)->not->toBeNull();
    expect($user->usesTwoFactorAuthentication())->toBeTrue();
    expect($user->two_factor_recovery_codes)->toHaveCount(8);

    foreach ($user->two_factor_recovery_codes as $code) {
        expect(mb_strlen($code))->toEqual(10);
    }

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorEnabled
            && $job->user->id === $user->id,
    );
});

it('refuses a code that is not right and leaves the account alone', function () {
    Queue::fake();

    $user = User::factory()->create([
        'two_factor_secret' => new Google2FA()->generateSecretKey(),
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    $confirmed = new ConfirmTwoFactorAuthentication(
        user: $user,
        code: '000000',
    )->execute();

    expect($confirmed)->toBeFalse();

    $user->refresh();

    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();

    Queue::assertNotPushed(LogUserAction::class);
});

it('refuses when no secret was ever written down', function () {
    Queue::fake();

    $user = User::factory()->create(['two_factor_secret' => null]);

    $confirmed = new ConfirmTwoFactorAuthentication(
        user: $user,
        code: '123456',
    )->execute();

    expect($confirmed)->toBeFalse();
    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();
});
