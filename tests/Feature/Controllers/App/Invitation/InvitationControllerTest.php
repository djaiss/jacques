<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Invitation\InvitationControllerTest;

use App\Enums\StaffInvitationStatusEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

const TOKEN = 'pambeeslyreceptionistdundermifflinscrantonbranchartschoolnewyork';

function invitation(array $state = []): StaffInvitation
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $pam = Staff::factory()->create(['company_id' => $company->id, 'first_name' => 'Pam', 'last_name' => 'Beesly', 'email' => 'pam@dundermifflin.com']);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Receptionist']);

    return StaffInvitation::factory()->sent()->create([
        'staff_id' => $pam->id,
        'role_id' => $role->id,
        'token_hash' => hash('sha256', TOKEN),
        ...$state,
    ]);
}

it('shows the salon and the role of the invitation', function () {
    invitation();

    $response = $this->get(route('invitations.show', ['token' => TOKEN]));

    $response->assertStatus(200);
    $response->assertSee('Join Dunder Mifflin');
    $response->assertSee('as Receptionist', false);
    $response->assertSee('pam@dundermifflin.com');
    $response->assertSee('Create account &amp; join team', false);
});

it('creates the account signs it in and welcomes it', function () {
    Queue::fake();

    $invitation = invitation();

    $response = $this->post(route('invitations.create', ['token' => TOKEN]), [
        'password' => 'jim-and-pam-2005',
    ]);

    $response->assertRedirect(route('dashboard.index'));
    $response->assertSessionHas('welcome', 'Dunder Mifflin');

    $user = User::query()->where('email', 'pam@dundermifflin.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($invitation->refresh()->status())->toBe(StaffInvitationStatusEnum::Accepted);
});

it('refuses a short password', function () {
    invitation();

    $response = $this->post(route('invitations.create', ['token' => TOKEN]), [
        'password' => 'beesly',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

it('names why a closed link no longer works', function (array $state, string $title) {
    invitation($state);

    $this->get(route('invitations.show', ['token' => TOKEN]))->assertSee($title);
})->with('closed invitations');

dataset('closed invitations', fn (): array => [
    'accepted' => [['accepted_at' => now()], 'This invitation was already used'],
    'revoked' => [['revoked_at' => now()], 'This invitation was withdrawn'],
    'expired' => [['sent_at' => now()->subDays(8), 'expires_at' => now()->subDay()], 'This invitation has expired'],
]);

it('returns to the link page when a closed invitation is submitted', function () {
    invitation(['revoked_at' => now()]);

    $response = $this->post(route('invitations.create', ['token' => TOKEN]), [
        'password' => 'jim-and-pam-2005',
    ]);

    $response->assertRedirect(route('invitations.show', ['token' => TOKEN]));
    $this->assertGuest();
});

it('says an unknown link is no longer valid', function () {
    $this->get(route('invitations.show', ['token' => str_repeat('a', 64)]))
        ->assertStatus(200)
        ->assertSee('This invitation is no longer valid');
});

it('asks somebody with an account to sign in and brings them back', function () {
    $invitation = invitation();
    User::factory()->create(['company_id' => $invitation->staff->company_id, 'email' => 'pam@dundermifflin.com']);

    $response = $this->get(route('invitations.show', ['token' => TOKEN]));

    $response->assertSee('Sign in to join Dunder Mifflin');
    $response->assertDontSee('Create account &amp; join team', false);
    $response->assertSessionHas('url.intended', route('invitations.show', ['token' => TOKEN]));
});

it('lets the invited account signed in join with one confirmation', function () {
    Queue::fake();

    $invitation = invitation();
    $pam = User::factory()->create(['company_id' => $invitation->staff->company_id, 'email' => 'pam@dundermifflin.com']);

    $this->actingAs($pam)->get(route('invitations.show', ['token' => TOKEN]))->assertSee('Join Dunder Mifflin');

    $response = $this->actingAs($pam)->post(route('invitations.create', ['token' => TOKEN]));

    $response->assertRedirect(route('dashboard.index'));
    expect($invitation->staff->refresh()->user_id)->toBe($pam->id);
});

it('asks somebody signed in with another account to switch and links nothing', function () {
    Queue::fake();

    $invitation = invitation();
    $michael = User::factory()->create(['company_id' => $invitation->staff->company_id, 'email' => 'michael@dundermifflin.com']);

    $this->actingAs($michael)
        ->get(route('invitations.show', ['token' => TOKEN]))
        ->assertSee('Sign out and continue');

    $response = $this->actingAs($michael)->post(route('invitations.create', ['token' => TOKEN]));

    $response->assertRedirect(route('invitations.show', ['token' => TOKEN]));
    expect($invitation->staff->refresh()->user_id)->toBeNull();
});

it('refuses an address already used by another salon', function () {
    invitation();
    User::factory()->create(['email' => 'pam@dundermifflin.com']);

    $this->get(route('invitations.show', ['token' => TOKEN]))
        ->assertSee('This address already has an account')
        ->assertDontSee('Create account &amp; join team', false);
});
