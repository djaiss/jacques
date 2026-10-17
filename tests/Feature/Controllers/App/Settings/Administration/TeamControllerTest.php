<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\TeamControllerTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('lists the team with the state of each invitation', function () {
    $michael = owner();
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Pam', 'last_name' => 'Beesly', 'display_name' => null, 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->sent()->create(['staff_id' => $pam->id]);

    $response = $this->actingAs($michael)->get(route('settings.team.index'));

    $response->assertStatus(200);
    $response->assertSee('Team members');
    $response->assertSee('Pam Beesly');
    $response->assertSee('Pending');
    $response->assertSee('Awaiting acceptance');
    $response->assertSee('Resend');
    $response->assertSee('Revoke');
});

it('offers to send a prepared invitation or cancel it', function () {
    $michael = owner();
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Jim', 'email' => 'jim@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $jim->id]);

    $response = $this->actingAs($michael)->get(route('settings.team.index'));

    $response->assertSee('Prepared');
    $response->assertSee(route('settings.staffInvitationSendings.create', $jim->id));
    $response->assertSee('Cancel the invitation of');
});

it('offers to prepare an invitation for somebody with an email address', function () {
    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Kevin', 'email' => 'kevin@dundermifflin.com']);

    $response = $this->actingAs($michael)->get(route('settings.team.index'));

    $response->assertSee(route('settings.staffInvitations.create', $kevin->id));
    $response->assertSee('Prepare invitation');
});

it('refuses somebody who may not manage roles', function () {
    $creed = User::factory()->create();

    $this->actingAs($creed)->get(route('settings.team.index'))->assertNotFound();
});
