<?php

declare(strict_types=1);

use App\Actions\CreateMagicLink;
use App\Enums\EmailTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('creates a link and emails it', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $result = new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();

    expect($result)->toBeInstanceOf(User::class);
    expect(MagicLink::query()->where('user_id', $user->id)->count())->toEqual(1);

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::MagicLinkCreated
            && $job->user->id === $user->id,
    );

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::MagicLinkCreated,
    );
});

it('never stores the token it puts in the email', function () {
    Queue::fake();

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();

    $stored = MagicLink::query()->firstOrFail();

    Queue::assertPushed(SendEmail::class, function (SendEmail $job) use ($stored): bool {
        preg_match('#/magic-link/([A-Za-z0-9]{64})#', $job->mailable->url, $matches);

        expect($matches)->not->toBeEmpty('The email should carry a 64 character token.');
        expect($stored->token)->not->toEqual($matches[1]);
        expect($stored->token)->toEqual(hash('sha256', $matches[1]));

        return true;
    });
});

it('gives the link a short life', function () {
    Queue::fake();
    config(['jacques.magic_link_duration_minutes' => 5]);

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();

    expect(MagicLink::query()->firstOrFail()->expires_at->timestamp)->toEqualWithDelta(now()->addMinutes(5)->timestamp, 2);
});

it('refuses an address that has no account', function () {
    Queue::fake();

    new CreateMagicLink(email: 'nobody@dundermifflin.com')->execute();
})->throws(ModelNotFoundException::class);

it('refuses a suspended user', function () {
    Queue::fake();

    User::factory()->inactive()->create(['email' => 'michael.scott@dundermifflin.com']);

    new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();
})->throws(ModelNotFoundException::class);
