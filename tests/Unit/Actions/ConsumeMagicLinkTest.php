<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\ConsumeMagicLinkTest;

use App\Actions\ConsumeMagicLink;
use App\Enums\EmailTypeEnum;
use App\Jobs\DetectSignInAddressChange;
use App\Jobs\SendEmail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

const TOKEN = 'aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkkllllmmmmnnnnoooopppp';

function linkFor(User $user, array $overrides = []): MagicLink
{
    return MagicLink::factory()->create(array_merge([
        'user_id' => $user->id,
        'token' => hash('sha256', TOKEN),
    ], $overrides));
}

it('signs the owner of the link in', function () {
    Queue::fake();

    $user = User::factory()->create();
    linkFor($user);

    $result = new ConsumeMagicLink(token: TOKEN, ip: '10.0.0.1')->execute();

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toEqual($user->id);
    expect($user->refresh()->last_signed_in_at)->not->toBeNull();

    Queue::assertPushedOn(
        queue: 'low',
        job: DetectSignInAddressChange::class,
        callback: fn (DetectSignInAddressChange $job): bool => $job->ip === '10.0.0.1',
    );
});

it('warns the user that somebody got in without a password', function () {
    Queue::fake();

    $user = User::factory()->create();
    linkFor($user);

    new ConsumeMagicLink(token: TOKEN, ip: '10.0.0.1')->execute();

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::MagicLinkSignIn
            && $job->user->id === $user->id,
    );
});

it('burns the link so it cannot be used twice', function () {
    Queue::fake();

    $user = User::factory()->create();
    $link = linkFor($user);

    new ConsumeMagicLink(token: TOKEN)->execute();

    expect($link->refresh()->used_at)->not->toBeNull();

    new ConsumeMagicLink(token: TOKEN)->execute();
})->throws(ModelNotFoundException::class);

it('refuses a link that ran out of time', function () {
    Queue::fake();

    linkFor(User::factory()->create(), ['expires_at' => now()->subMinute()]);

    new ConsumeMagicLink(token: TOKEN)->execute();
})->throws(ModelNotFoundException::class);

it('refuses a token it has never seen', function () {
    Queue::fake();

    new ConsumeMagicLink(token: TOKEN)->execute();
})->throws(ModelNotFoundException::class);

it('refuses a link belonging to a suspended user', function () {
    Queue::fake();

    linkFor(User::factory()->inactive()->create());

    new ConsumeMagicLink(token: TOKEN)->execute();
})->throws(ModelNotFoundException::class);
