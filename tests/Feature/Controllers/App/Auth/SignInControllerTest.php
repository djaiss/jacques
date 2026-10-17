<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('shows the sign in page', function () {
    $response = $this->get(route('auth.signIn.new'));

    $response->assertStatus(200);
    $response->assertSee('Welcome back');
});

it('signs a user in', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.signIn.create'), [
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home.index'));
    $this->assertAuthenticatedAs($user);
});

it('refuses a wrong password', function () {
    Queue::fake();

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.signIn.create'), [
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'thatswhatshesaid',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('refuses a suspended user', function () {
    Queue::fake();

    User::factory()->inactive()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.signIn.create'), [
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('sends somebody who uses two factor to the challenge', function () {
    Queue::fake();

    $user = User::factory()->twoFactor()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.signIn.create'), [
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('auth.twoFactor.new'));
    $response->assertSessionHas('twoFactor.user.id', $user->id);

    $this->assertGuest();
});

it('does not show the human check when it is turned off', function () {
    $this->get(route('auth.signIn.new'))
        ->assertOk()
        ->assertDontSee('cf-turnstile');
});

it('shows the logo with a sharper copy for dense screens', function () {
    $response = $this->get(route('auth.signIn.new'));

    $response->assertSee('img/logo.webp', false);
    $response->assertSee('img/logo@2x.webp 2x', false);
});

it('shows the human check when it is turned on', function () {
    config(['turnstile.enabled' => true]);
    config(['turnstile.site_key' => 'the-key-of-the-one-with-the-widget']);

    $this->get(route('auth.signIn.new'))
        ->assertOk()
        ->assertSee('cf-turnstile')
        ->assertSee('the-key-of-the-one-with-the-widget');
});

it('refuses a sign in without a human check', function () {
    config(['turnstile.enabled' => true]);
    Http::fake();

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.signIn.create'), [
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('cf-turnstile-response');
    $this->assertGuest();
});

it('signs a user in when cloudflare accepts the human check', function () {
    Queue::fake();
    config(['turnstile.enabled' => true]);
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.signIn.create'), [
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'password',
        'cf-turnstile-response' => 'a-token-cloudflare-likes',
    ]);

    $response->assertRedirect(route('home.index'));
    $this->assertAuthenticatedAs($user);
});

it('refuses the sign in when cloudflare rejects the human check', function () {
    config(['turnstile.enabled' => true]);
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.signIn.create'), [
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'password',
        'cf-turnstile-response' => 'a-token-that-was-already-used',
    ]);

    $response->assertSessionHasErrors('cf-turnstile-response');
    $this->assertGuest();
});

it('links to the other ways in', function () {
    $this->get(route('auth.signIn.new'))
        ->assertOk()
        ->assertSee(route('auth.magicLink.new'))
        ->assertSee(route('auth.password.new'))
        ->assertSee(route('auth.register.new'));
});

it('signs a user out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('auth.signIn.destroy'));

    $response->assertRedirect(route('home.index'));
    $this->assertGuest();
});

it('keeps somebody who is signed in away from the sign in page', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('auth.signIn.new'));

    $response->assertRedirect(route('home.index'));
});
