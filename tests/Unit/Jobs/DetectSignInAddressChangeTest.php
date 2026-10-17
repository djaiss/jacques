<?php

declare(strict_types=1);

use App\Enums\EmailTypeEnum;
use App\Jobs\DetectSignInAddressChange;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('says nothing the first time somebody signs in', function () {
    Queue::fake();

    $user = User::factory()->create(['last_sign_in_ip' => null]);

    new DetectSignInAddressChange(user: $user, ip: '10.0.0.1')->handle();

    expect($user->refresh()->last_sign_in_ip)->toEqual('10.0.0.1');

    Queue::assertNotPushed(SendEmail::class);
});

it('says nothing when the address did not move', function () {
    Queue::fake();

    $user = User::factory()->create(['last_sign_in_ip' => '10.0.0.1']);

    new DetectSignInAddressChange(user: $user, ip: '10.0.0.1')->handle();

    Queue::assertNotPushed(SendEmail::class);
});

it('warns the user when the address moved', function () {
    Queue::fake();

    $user = User::factory()->create(['last_sign_in_ip' => '10.0.0.1']);

    new DetectSignInAddressChange(user: $user, ip: '192.168.1.1')->handle();

    expect($user->refresh()->last_sign_in_ip)->toEqual('192.168.1.1');

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::SignInFromNewAddress
            && $job->user->id === $user->id,
    );
});
