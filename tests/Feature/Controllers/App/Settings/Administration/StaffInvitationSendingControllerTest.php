<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\StaffInvitationSendingControllerTest;

use App\Enums\StaffInvitationStatusEnum;
use App\Jobs\SendEmail;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function invitedJim(User $owner): Staff
{
    $jim = Staff::factory()->create(['company_id' => $owner->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null, 'email' => 'jim@dundermifflin.com']);
    $role = Role::query()->where('company_id', $owner->company_id)->where('slug', Role::STAFF)->firstOrFail();
    StaffInvitation::factory()->create(['staff_id' => $jim->id, 'role_id' => $role->id]);

    return $jim;
}

it('sends the invitation', function () {
    Queue::fake();

    $michael = owner();
    $jim = invitedJim($michael);

    $response = $this->actingAs($michael)->post(route('settings.staffInvitationSendings.create', $jim->id));

    $response->assertRedirect(route('settings.team.show', $jim->id));
    $response->assertSessionHas('status', 'The invitation is on its way.');
    expect($jim->invitation->refresh()->status())->toBe(StaffInvitationStatusEnum::Pending);
    Queue::assertPushedOn('high', SendEmail::class);
});

it('explains why a fourth send in a day is refused', function () {
    Queue::fake();

    $michael = owner();
    $jim = invitedJim($michael);
    RateLimiter::hit("staff-invitation:{$jim->id}", 86400);
    RateLimiter::hit("staff-invitation:{$jim->id}", 86400);
    RateLimiter::hit("staff-invitation:{$jim->id}", 86400);

    $response = $this->actingAs($michael)
        ->from(route('settings.team.index'))
        ->post(route('settings.staffInvitationSendings.create', $jim->id));

    $response->assertRedirect(route('settings.team.index'));
    $response->assertSessionHasErrors('invitation');
    Queue::assertNotPushed(SendEmail::class);
});

it('explains why a revoked invitation cannot be sent', function () {
    $michael = owner();
    $jim = invitedJim($michael);
    $jim->invitation->update(['sent_at' => now(), 'expires_at' => now()->addDays(7), 'revoked_at' => now()]);

    $this->actingAs($michael)
        ->post(route('settings.staffInvitationSendings.create', $jim->id))
        ->assertSessionHasErrors('panel');
});

it('prepares and sends an invitation with the chosen role', function () {
    Queue::fake();

    $michael = owner();
    $toby = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'toby@dundermifflin.com']);
    $manager = Role::query()->where('company_id', $michael->company_id)->where('slug', Role::MANAGER)->firstOrFail();

    $response = $this->actingAs($michael)->post(route('settings.staffInvitationSendings.create', $toby->id), [
        'role_id' => $manager->id,
    ]);

    $response->assertRedirect(route('settings.team.show', $toby->id));
    expect($toby->refresh()->invitation->role_id)->toBe($manager->id);
    expect($toby->invitation->status())->toBe(StaffInvitationStatusEnum::Pending);
    Queue::assertPushedOn('high', SendEmail::class);
});

it('does not find a role of another company', function () {
    $michael = owner();
    $jim = invitedJim($michael);

    $this->actingAs($michael)
        ->post(route('settings.staffInvitationSendings.create', $jim->id), ['role_id' => Role::factory()->create()->id])
        ->assertNotFound();
});

it('refuses somebody who may not manage roles', function () {
    $michael = owner();
    $jim = invitedJim($michael);
    $creed = User::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($creed)
        ->post(route('settings.staffInvitationSendings.create', $jim->id))
        ->assertNotFound();
});

it('does not find a staff member of another company', function () {
    $michael = owner();
    $jim = invitedJim(owner());

    $this->actingAs($michael)
        ->post(route('settings.staffInvitationSendings.create', $jim->id))
        ->assertNotFound();
});
