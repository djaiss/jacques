<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth\RegistrationControllerTest;

use App\Enums\EmailTypeEnum;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * @return array<string, string>
 */
function validPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'company_name' => 'Dunder Mifflin',
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'thatswhatshesaid',
        'password_confirmation' => 'thatswhatshesaid',
        'terms' => '1',
    ], $overrides);
}

it('shows the registration page', function () {
    $response = $this->get(route('auth.register.new'));

    $response->assertStatus(200);
    $response->assertSee('Create your account');
});

it('ties the password hint to the password field', function () {
    $response = $this->get(route('auth.register.new'));

    $response->assertSee('aria-describedby="password-help"', escape: false);
    $response->assertSee('id="password-help"', escape: false);
    $response->assertDontSee('x-bind:disabled', escape: false);
});

it('links to the terms of use and the privacy policy', function () {
    $response = $this->get(route('auth.register.new'));

    $response->assertSee(config('jacques.terms_url'));
    $response->assertSee(config('jacques.privacy_url'));
});

it('creates a company an owner and a staff record', function () {
    Queue::fake();

    $response = $this->post(route('auth.register.create'), validPayload());

    $response->assertRedirect(route('auth.verification.notice'));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

    expect(Company::query()->count())->toEqual(1);
    expect($user->company->name)->toEqual('Dunder Mifflin');
    expect($user->isOwner())->toBeTrue();
    expect(Staff::query()->count())->toEqual(1);
    expect($user->staff->name)->toEqual('Michael Scott');
});

it('sends the verification email and records it', function () {
    $this->post(route('auth.register.create'), validPayload());

    $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

    $this->assertDatabaseHas('emails_sent', [
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'email_type' => EmailTypeEnum::EmailVerification->value,
        'email_address' => 'michael.scott@dundermifflin.com',
        'subject' => 'Confirm your email address',
    ]);
});

it('refuses somebody who has not agreed to the terms', function () {
    $response = $this->post(route('auth.register.create'), validPayload(['terms' => '']));

    $response->assertSessionHasErrors('terms');
    $this->assertGuest();
    expect(User::query()->count())->toEqual(0);
});

it('refuses an email address that is already taken', function () {
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.register.create'), validPayload());

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('refuses a password that is too short', function () {
    $response = $this->post(route('auth.register.create'), validPayload([
        'password' => 'short',
        'password_confirmation' => 'short',
    ]));

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

it('refuses a password that is not confirmed', function () {
    $response = $this->post(route('auth.register.create'), validPayload([
        'password_confirmation' => 'bearsbeatsbattlestar',
    ]));

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

it('refuses a registration without a human check', function () {
    config(['turnstile.enabled' => true]);
    Http::fake();

    $response = $this->post(route('auth.register.create'), validPayload());

    $response->assertSessionHasErrors('cf-turnstile-response');
    $this->assertGuest();
});

it('creates the account when cloudflare accepts the human check', function () {
    Queue::fake();
    config(['turnstile.enabled' => true]);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    $response = $this->post(route('auth.register.create'), validPayload([
        'cf-turnstile-response' => 'a-token-cloudflare-likes',
    ]));

    $response->assertRedirect(route('auth.verification.notice'));
    $this->assertAuthenticated();
});

it('refuses the registration when cloudflare rejects the human check', function () {
    config(['turnstile.enabled' => true]);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);

    $response = $this->post(route('auth.register.create'), validPayload([
        'cf-turnstile-response' => 'a-token-cloudflare-does-not-like',
    ]));

    $response->assertSessionHasErrors('cf-turnstile-response');
    $this->assertGuest();
});
