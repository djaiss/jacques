<?php

declare(strict_types=1);

use App\Actions\DisableTwoFactorAuthentication;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('forgets the secret and the recovery codes', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

    $result = new DisableTwoFactorAuthentication(user: $user)->execute();

    expect($result)->toBeInstanceOf(User::class);

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
    expect($user->usesTwoFactorAuthentication())->toBeFalse();
});

it('logs the change', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

    new DisableTwoFactorAuthentication(user: $user)->execute();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorDisabled
            && $job->company->id === $company->id
            && $job->user->id === $user->id,
    );
});
