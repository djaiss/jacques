<?php

declare(strict_types=1);

use App\Actions\SendEmailVerification;
use App\Enums\EmailTypeEnum;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('sends the email that confirms the address', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();

    $result = new SendEmailVerification(user: $user)->execute();

    expect($result)->toBeInstanceOf(User::class);

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::EmailVerification
            && $job->user->id === $user->id
            && $job->company->id === $user->company_id,
    );
});

it('signs the link so it cannot be forged', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();

    new SendEmailVerification(user: $user)->execute();

    Queue::assertPushed(
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => str_contains($job->mailable->url, 'signature=')
            && str_contains($job->mailable->url, '/verify-email/'.$user->id.'/'.sha1((string) $user->email)),
    );
});
