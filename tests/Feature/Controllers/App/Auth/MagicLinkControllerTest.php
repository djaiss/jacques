<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth\MagicLinkControllerTest;

use App\Enums\EmailTypeEnum;
use App\Jobs\SendEmail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

const TOKEN = 'aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkkllllmmmmnnnnoooopppp';

it('shows the page that asks for a link', function () {
    $response = $this->get(route('auth.magicLink.new'));

    $response->assertStatus(200);
    $response->assertSee('Get a link to sign in');
});

it('emails a link', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->post(route('auth.magicLink.create'), [
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    $response->assertStatus(200);
    $response->assertSee('Check your inbox');

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::MagicLinkCreated
            && $job->user->id === $user->id,
    );
});

it('records the email it sends', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->post(route('auth.magicLink.create'), [
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    $this->assertDatabaseHas('emails_sent', [
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'email_type' => EmailTypeEnum::MagicLinkCreated->value,
        'email_address' => 'michael.scott@dundermifflin.com',
    ]);
});

it('says the same thing when the address has no account', function () {
    Queue::fake();

    $response = $this->post(route('auth.magicLink.create'), [
        'email' => 'nobody@dundermifflin.com',
    ]);

    $response->assertStatus(200);
    $response->assertSee('Check your inbox');

    Queue::assertNotPushed(SendEmail::class);
});

it('signs a user in through the link', function () {
    Queue::fake();

    $user = User::factory()->create();
    MagicLink::factory()->create([
        'user_id' => $user->id,
        'token' => hash('sha256', TOKEN),
    ]);

    $response = $this->get(route('auth.magicLink.show', ['token' => TOKEN]));

    $response->assertRedirect(route('home.index'));
    $this->assertAuthenticatedAs($user);
});

it('sends somebody who uses two factor to the challenge', function () {
    Queue::fake();

    $user = User::factory()->twoFactor()->create();
    MagicLink::factory()->create([
        'user_id' => $user->id,
        'token' => hash('sha256', TOKEN),
    ]);

    $response = $this->get(route('auth.magicLink.show', ['token' => TOKEN]));

    $response->assertRedirect(route('auth.twoFactor.new'));
    $response->assertSessionHas('twoFactor.user.id', $user->id);
    $this->assertGuest();
});

it('refuses a link that was already used', function () {
    Queue::fake();

    $user = User::factory()->create();
    MagicLink::factory()->create([
        'user_id' => $user->id,
        'token' => hash('sha256', TOKEN),
    ]);

    $this->get(route('auth.magicLink.show', ['token' => TOKEN]));

    $this->post(route('auth.signIn.destroy'));

    $response = $this->get(route('auth.magicLink.show', ['token' => TOKEN]));

    $response->assertRedirect(route('auth.magicLink.new'));
    $this->assertGuest();
});

it('refuses a link that ran out of time', function () {
    Queue::fake();

    MagicLink::factory()->expired()->create([
        'user_id' => User::factory()->create()->id,
        'token' => hash('sha256', TOKEN),
    ]);

    $response = $this->get(route('auth.magicLink.show', ['token' => TOKEN]));

    $response->assertRedirect(route('auth.magicLink.new'));
    $this->assertGuest();
});

it('refuses a token it has never seen', function () {
    $response = $this->get(route('auth.magicLink.show', ['token' => TOKEN]));

    $response->assertRedirect(route('auth.magicLink.new'));
    $this->assertGuest();
});

it('refuses a disposable address', function () {
    Queue::fake();

    $response = $this->post(route('auth.magicLink.create'), [
        'email' => 'michael.scott@mailinator.com',
    ]);

    $response->assertSessionHasErrors('email');
    Queue::assertNotPushed(SendEmail::class);
});
