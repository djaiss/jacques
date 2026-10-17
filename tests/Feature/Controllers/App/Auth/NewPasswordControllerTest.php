<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;

it('shows the page that chooses a new password', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->get(route('auth.password.edit', [
        'token' => Password::createToken($user),
        'email' => 'michael.scott@dundermifflin.com',
    ]));

    $response->assertStatus(200);
    $response->assertSee('Choose a new password');
    $response->assertSee('michael.scott@dundermifflin.com');
});

it('changes the password', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    $token = Password::createToken($user);

    $response = $this->post(route('auth.password.update'), [
        'token' => $token,
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'bearsbeatsbattlestar',
        'password_confirmation' => 'bearsbeatsbattlestar',
    ]);

    $response->assertRedirect(route('auth.signIn.new'));
    $response->assertSessionHas('status');

    expect(Hash::check('bearsbeatsbattlestar', $user->refresh()->password_hash))->toBeTrue();
});

it('throws the token away once it is used', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    $token = Password::createToken($user);

    $payload = [
        'token' => $token,
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'bearsbeatsbattlestar',
        'password_confirmation' => 'bearsbeatsbattlestar',
    ];

    $this->post(route('auth.password.update'), $payload);

    $response = $this->post(route('auth.password.update'), $payload);

    $response->assertSessionHasErrors('email');
});

it('refuses a token that is not right', function () {
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.password.update'), [
        'token' => str_repeat('a', 64),
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'bearsbeatsbattlestar',
        'password_confirmation' => 'bearsbeatsbattlestar',
    ]);

    $response->assertSessionHasErrors('email');
});

it('refuses a password that is too short', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.password.update'), [
        'token' => Password::createToken($user),
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});

it('refuses a password that is not confirmed', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.password.update'), [
        'token' => Password::createToken($user),
        'email' => 'michael.scott@dundermifflin.com',
        'password' => 'bearsbeatsbattlestar',
        'password_confirmation' => 'thatswhatshesaid',
    ]);

    $response->assertSessionHasErrors('password');
});
