<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('shows the page that asks for a reset link', function () {
    $response = $this->get(route('auth.password.new'));

    $response->assertStatus(200);
    $response->assertSee('Forgot your password?');
});

it('sends a reset link', function () {
    Notification::fake();

    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->from(route('auth.password.new'))
        ->post(route('auth.password.create'), ['email' => 'michael.scott@dundermifflin.com']);

    $response->assertRedirect(route('auth.password.new'));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'michael.scott@dundermifflin.com',
    ]);
});

it('says the same thing when the address has no account', function () {
    Notification::fake();

    $response = $this->from(route('auth.password.new'))
        ->post(route('auth.password.create'), ['email' => 'nobody@dundermifflin.com']);

    $response->assertRedirect(route('auth.password.new'));
    $response->assertSessionHas('status');
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseCount('password_reset_tokens', 0);
});

it('refuses something that is not an address', function () {
    $response = $this->post(route('auth.password.create'), ['email' => 'that is not an email']);

    $response->assertSessionHasErrors('email');
});
