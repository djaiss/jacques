<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth\EmailVerificationControllerTest;

use App\Enums\EmailTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

function verificationUrl(User $user): string
{
    return URL::temporarySignedRoute(
        'auth.verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1((string) $user->email),
        ],
    );
}

it('shows the notice to somebody who has not confirmed yet', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('auth.verification.notice'));

    $response->assertStatus(200);
    $response->assertSee($user->email);
});

it('sends somebody who has already confirmed back home', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('auth.verification.notice'));

    $response->assertRedirect(route('home.index'));
});

it('confirms the email address', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(verificationUrl($user));

    $response->assertRedirect(route('home.index'));
    expect($user->refresh()->hasConfirmedEmail())->toBeTrue();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmailConfirmed,
    );
});

it('refuses a link whose hash does not match', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'auth.verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1('dwight.schrute@dundermifflin.com'),
        ],
    );

    $response = $this->actingAs($user)->get($url);

    $response->assertStatus(403);
    expect($user->refresh()->hasConfirmedEmail())->toBeFalse();
});

it('refuses a link that is not signed', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('auth.verification.verify', [
        'id' => $user->id,
        'hash' => sha1((string) $user->email),
    ]));

    $response->assertStatus(403);
    expect($user->refresh()->hasConfirmedEmail())->toBeFalse();
});

it('sends the email again', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->from(route('auth.verification.notice'))
        ->post(route('auth.verification.send'));

    $response->assertRedirect(route('auth.verification.notice'));
    $response->assertSessionHas('status', 'We sent you another email.');

    $this->actingAs($user)
        ->get(route('auth.verification.notice'))
        ->assertSee('role="status"', escape: false)
        ->assertSee('We sent you another email.');

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::EmailVerification
            && $job->user->id === $user->id,
    );
});

it('does not send the email again to somebody who has already confirmed', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('auth.verification.send'));

    $response->assertRedirect(route('home.index'));

    Queue::assertNotPushed(SendEmail::class);
});
