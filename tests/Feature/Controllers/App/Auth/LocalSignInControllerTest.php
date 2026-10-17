<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Queue;

it('hides the shortcut outside the local environment', function () {
    $response = $this->get(route('auth.signIn.new'));

    $response->assertStatus(200);
    $response->assertDontSee('Sign in as michael.scott@dundermifflin.com');
});

it('shows the shortcut in the local environment', function () {
    $this->app->detectEnvironment(fn (): string => 'local');

    $response = $this->get(route('auth.signIn.new'));

    $response->assertStatus(200);
    $response->assertSee('Sign in as michael.scott@dundermifflin.com');
});

it('signs the seeded account in', function () {
    Queue::fake();

    $this->app->detectEnvironment(fn (): string => 'local');
    $this->withoutMiddleware(PreventRequestForgery::class);

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.localSignIn.create'));

    $response->assertRedirect(route('settings.profile.index'));
    $this->assertAuthenticatedAs($user);
});

it('refuses to sign anybody in outside the local environment', function () {
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.localSignIn.create'));

    $response->assertStatus(404);
    $this->assertGuest();
});

it('reports a database that was never seeded', function () {
    $this->app->detectEnvironment(fn (): string => 'local');
    $this->withoutMiddleware(PreventRequestForgery::class);

    $response = $this->post(route('auth.localSignIn.create'));

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('sends a signed in visitor away', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('auth.localSignIn.create'));

    $response->assertRedirect(route('home.index'));
});
