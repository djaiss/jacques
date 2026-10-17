<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\StaffInvitationRevocationControllerTest;

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

it('revokes a pending invitation', function () {
    Queue::fake();

    $michael = owner();
    $ryan = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Ryan', 'last_name' => 'Howard', 'display_name' => null, 'email' => 'ryan@dundermifflin.com']);
    $invitation = StaffInvitation::factory()->sent()->create(['staff_id' => $ryan->id]);

    $response = $this->actingAs($michael)->post(route('settings.staffInvitationRevocations.create', $ryan->id));

    $response->assertRedirect(route('settings.team.index'));
    $response->assertSessionHas('status', 'The invitation is revoked.');
    $response->assertSessionHas('status_description', 'The link sent to Ryan Howard no longer works.');
    expect($invitation->refresh()->status())->toBe(StaffInvitationStatusEnum::Revoked);
});

it('explains why a prepared invitation cannot be revoked', function () {
    $michael = owner();
    $ryan = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'ryan@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $ryan->id]);

    $this->actingAs($michael)
        ->post(route('settings.staffInvitationRevocations.create', $ryan->id))
        ->assertSessionHasErrors('invitation');
});

it('does not find a staff member of another company', function () {
    $michael = owner();
    $ryan = Staff::factory()->create(['email' => 'ryan@dundermifflin.com']);
    StaffInvitation::factory()->sent()->create(['staff_id' => $ryan->id]);

    $this->actingAs($michael)
        ->post(route('settings.staffInvitationRevocations.create', $ryan->id))
        ->assertNotFound();
});
