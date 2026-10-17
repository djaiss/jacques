<?php

declare(strict_types=1);

use App\Actions\RegenerateTwoFactorRecoveryCodes;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('hands out a new set and throws the old one away', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

    new RegenerateTwoFactorRecoveryCodes(user: $user)->execute();

    $codes = $user->refresh()->two_factor_recovery_codes;

    expect($codes)->toHaveCount(8);
    expect($codes)->not->toContain('scranton-1');
    expect($codes)->not->toContain('scranton-2');
});

it('logs the change', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

    new RegenerateTwoFactorRecoveryCodes(user: $user)->execute();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorRecoveryCodesRegenerated
            && $job->user->id === $user->id,
    );
});

it('throws when two factor authentication is not in use', function () {
    $user = User::factory()->create(['two_factor_confirmed_at' => null]);

    new RegenerateTwoFactorRecoveryCodes(user: $user)->execute();
})->throws(ModelNotFoundException::class);
