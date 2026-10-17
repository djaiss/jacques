<?php

declare(strict_types=1);

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

it('changes the password', function () {
    Queue::fake();

    $user = User::factory()->create([
        'password_hash' => Hash::make('beets-bears-battlestar'),
        'password_changed_at' => null,
    ]);

    $response = $this->actingAs($user)->put(route('settings.password.update'), [
        'current_password' => 'beets-bears-battlestar',
        'new_password' => 'schrute-farms-2005',
        'new_password_confirmation' => 'schrute-farms-2005',
    ]);

    $response->assertRedirect(route('settings.security.index'));
    $response->assertSessionHas('status', 'Your password is changed.');
    $response->assertSessionHas('status_description', 'Use it the next time you sign in.');

    expect(Hash::check('schrute-farms-2005', $user->refresh()->password_hash))->toBeTrue();

    expect($user->refresh()->password_changed_at?->timestamp)->toEqualWithDelta(now()->timestamp, 2);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdated
            && $job->user->id === $user->id,
    );
});

it('refuses a wrong current password', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('beets-bears-battlestar'),
    ]);

    $response = $this->actingAs($user)->put(route('settings.password.update'), [
        'current_password' => 'that-is-what-she-said',
        'new_password' => 'schrute-farms-2005',
        'new_password_confirmation' => 'schrute-farms-2005',
    ]);

    $response->assertSessionHasErrors('current_password');

    expect(Hash::check('beets-bears-battlestar', $user->refresh()->password_hash))->toBeTrue();
});

it('refuses a new password that is not confirmed', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('beets-bears-battlestar'),
    ]);

    $response = $this->actingAs($user)->put(route('settings.password.update'), [
        'current_password' => 'beets-bears-battlestar',
        'new_password' => 'schrute-farms-2005',
        'new_password_confirmation' => 'dunder-mifflin-2005',
    ]);

    $response->assertSessionHasErrors('new_password');

    expect(Hash::check('beets-bears-battlestar', $user->refresh()->password_hash))->toBeTrue();
});

it('refuses a new password that is too short', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('beets-bears-battlestar'),
    ]);

    $response = $this->actingAs($user)->put(route('settings.password.update'), [
        'current_password' => 'beets-bears-battlestar',
        'new_password' => 'beets',
        'new_password_confirmation' => 'beets',
    ]);

    $response->assertSessionHasErrors('new_password');
});

it('redirects a visitor who is not signed in', function () {
    $response = $this->put(route('settings.password.update'), []);

    $response->assertRedirect(route('auth.signIn.new'));
});
