<?php

declare(strict_types=1);

use App\Actions\AttemptSignIn;
use App\Enums\EmailTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\DetectSignInAddressChange;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

it('signs a user in', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $result = new AttemptSignIn(
        email: 'michael.scott@dundermifflin.com',
        password: 'password',
        ip: '10.0.0.1',
    )->execute();

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toEqual($user->id);
    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->last_signed_in_at)->not->toBeNull();

    Queue::assertPushedOn(
        queue: 'low',
        job: DetectSignInAddressChange::class,
        callback: fn (DetectSignInAddressChange $job): bool => $job->user->id === $user->id && $job->ip === '10.0.0.1',
    );

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserSignedIn,
    );
});

it('matches the address whatever the casing', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    new AttemptSignIn(
        email: 'Michael.Scott@DunderMifflin.com',
        password: 'password',
    )->execute();

    $this->assertAuthenticatedAs($user);
});

it('refuses a wrong password and warns the owner', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    try {
        new AttemptSignIn(
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->fail('The action should have refused.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('email');
    }

    $this->assertGuest();

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::SignInFailed
            && $job->user->id === $user->id,
    );
});

it('says nothing about an address that has no account', function () {
    Queue::fake();

    try {
        new AttemptSignIn(
            email: 'nobody@dundermifflin.com',
            password: 'password',
        )->execute();
    } finally {
        Queue::assertNotPushed(SendEmail::class);
    }
})->throws(ValidationException::class);

it('refuses a suspended user', function () {
    Queue::fake();

    $user = User::factory()->inactive()->create(['email' => 'michael.scott@dundermifflin.com']);

    try {
        new AttemptSignIn(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
        )->execute();
    } finally {
        $this->assertGuest();
        Queue::assertPushed(
            SendEmail::class,
            fn (SendEmail $job): bool => $job->user->id === $user->id,
        );
    }
})->throws(ValidationException::class);

it('refuses somebody who signs in through single sign on', function () {
    Queue::fake();

    User::factory()->singleSignOn()->create(['email' => 'michael.scott@dundermifflin.com']);

    try {
        new AttemptSignIn(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
        )->execute();
    } finally {
        $this->assertGuest();
    }
})->throws(ValidationException::class);

it('stops trying after five failures', function () {
    Queue::fake();

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        try {
            new AttemptSignIn(
                email: 'michael.scott@dundermifflin.com',
                password: 'wrong',
                ip: '10.0.0.1',
            )->execute();
        } catch (ValidationException) {
        }
    }

    try {
        new AttemptSignIn(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
            ip: '10.0.0.1',
        )->execute();

        $this->fail('The action should have been rate limited.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['email'][0])->toContain('Too many sign-in attempts');
    }

    $this->assertGuest();
});

it('forgets the failures once somebody gets in', function () {
    Queue::fake();

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    try {
        new AttemptSignIn(
            email: 'michael.scott@dundermifflin.com',
            password: 'wrong',
            ip: '10.0.0.1',
        )->execute();
    } catch (ValidationException) {
    }

    new AttemptSignIn(
        email: 'michael.scott@dundermifflin.com',
        password: 'password',
        ip: '10.0.0.1',
    )->execute();

    expect(RateLimiter::attempts('michael.scott@dundermifflin.com|10.0.0.1'))->toEqual(0);
});

it('writes a remember token when asked to', function () {
    Queue::fake();

    User::factory()->create([
        'email' => 'michael.scott@dundermifflin.com',
        'remember_token' => null,
    ]);

    $user = new AttemptSignIn(
        email: 'michael.scott@dundermifflin.com',
        password: 'password',
        remember: true,
    )->execute();

    expect($user->refresh()->remember_token)->not->toBeNull();
});

it('leaves no remember token when not asked to', function () {
    Queue::fake();

    User::factory()->create([
        'email' => 'michael.scott@dundermifflin.com',
        'remember_token' => null,
    ]);

    $user = new AttemptSignIn(
        email: 'michael.scott@dundermifflin.com',
        password: 'password',
    )->execute();

    expect($user->refresh()->remember_token)->toBeNull();
});
