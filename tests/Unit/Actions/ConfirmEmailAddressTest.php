<?php

declare(strict_types=1);

use App\Actions\ConfirmEmailAddress;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('confirms the email address of a user', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();

    $result = new ConfirmEmailAddress(user: $user)->execute();

    expect($result)->toBeInstanceOf(User::class);
    expect($user->refresh()->hasConfirmedEmail())->toBeTrue();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmailConfirmed
            && $job->user->id === $user->id
            && $job->company->id === $user->company_id,
    );
});
