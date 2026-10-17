<?php

declare(strict_types=1);

use App\Actions\UpdateUserPassword;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('updates the password', function () {
    Queue::fake();

    $user = User::factory()->create([
        'password_hash' => Hash::make('beets'),
        'password_changed_at' => null,
    ]);

    $result = new UpdateUserPassword(
        user: $user,
        password: 'bearsbeatsbattlestar',
    )->execute();

    expect($result)->toBeInstanceOf(User::class);
    expect(Hash::check('bearsbeatsbattlestar', $user->refresh()->password_hash))->toBeTrue();

    expect($user->refresh()->password_changed_at?->timestamp)->toEqualWithDelta(now()->timestamp, 2);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdated
            && $job->company->id === $user->company_id
            && $job->user->id === $user->id,
    );
});

it('throws when the user signs in through sso', function () {
    $user = User::factory()->singleSignOn()->create();

    new UpdateUserPassword(
        user: $user,
        password: 'bearsbeatsbattlestar',
    )->execute();
})->throws(ValidationException::class);
