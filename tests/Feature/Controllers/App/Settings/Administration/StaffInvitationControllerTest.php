<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\StaffInvitationControllerTest;

use App\Enums\StaffInvitationStatusEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function role(User $user, string $slug): Role
{
    return Role::query()->where('company_id', $user->company_id)->where('slug', $slug)->firstOrFail();
}

it('prepares an invitation with a role', function () {
    Queue::fake();

    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'kevin@dundermifflin.com']);

    $response = $this->actingAs($michael)->post(route('settings.staffInvitations.create', $kevin->id), [
        'role_id' => role($michael, Role::STAFF)->id,
    ]);

    $response->assertRedirect(route('settings.team.show', $kevin->id));
    $response->assertSessionHas('status', 'The invitation is ready.');
    $this->assertDatabaseHas('staff_invitations', ['staff_id' => $kevin->id, 'role_id' => role($michael, Role::STAFF)->id]);
});

it('prepares a revoked invitation again', function () {
    Queue::fake();

    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'kevin@dundermifflin.com']);
    $invitation = StaffInvitation::factory()->revoked()->create(['staff_id' => $kevin->id]);

    $this->actingAs($michael)->post(route('settings.staffInvitations.create', $kevin->id), [
        'role_id' => role($michael, Role::STAFF)->id,
    ]);

    expect($invitation->refresh()->status())->toBe(StaffInvitationStatusEnum::Prepared);
});

it('cancels a prepared invitation', function () {
    Queue::fake();

    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'kevin@dundermifflin.com']);
    $invitation = StaffInvitation::factory()->create(['staff_id' => $kevin->id]);

    $response = $this->actingAs($michael)->delete(route('settings.staffInvitations.destroy', $kevin->id));

    $response->assertRedirect(route('settings.team.show', $kevin->id));
    $response->assertSessionHas('status', 'The invitation is cancelled.');
    $this->assertModelMissing($invitation);
});

it('cancels an invitation already sent', function () {
    Queue::fake();

    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'kevin@dundermifflin.com']);
    $invitation = StaffInvitation::factory()->sent()->create(['staff_id' => $kevin->id]);

    $this->actingAs($michael)->delete(route('settings.staffInvitations.destroy', $kevin->id));

    $this->assertModelMissing($invitation);
});

it('does not find a role of another company', function () {
    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'kevin@dundermifflin.com']);

    $this->actingAs($michael)
        ->post(route('settings.staffInvitations.create', $kevin->id), ['role_id' => Role::factory()->create()->id])
        ->assertNotFound();
});

it('does not find a staff member of another company', function () {
    $michael = owner();
    $toby = Staff::factory()->create(['email' => 'toby@dundermifflin.com']);

    $this->actingAs($michael)
        ->post(route('settings.staffInvitations.create', $toby->id), ['role_id' => role($michael, Role::STAFF)->id])
        ->assertNotFound();
});
