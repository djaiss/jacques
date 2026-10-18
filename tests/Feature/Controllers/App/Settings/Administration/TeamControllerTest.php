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
    $michael = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'display_name' => null]);

    return $michael;
}

it('lists the team with their filters', function () {
    $michael = owner();
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Pam', 'last_name' => 'Beesly', 'display_name' => null, 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $pam->id]);
    Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Toby', 'last_name' => 'Flenderson', 'display_name' => null]);

    $response = $this->actingAs($michael)->get(route('settings.team.index'));

    $response->assertStatus(200);
    $response->assertSee('Michael Scott');
    $response->assertSee('Pam Beesly');
    $response->assertSee('Toby Flenderson');
    $response->assertSee('Everybody');
    $response->assertSee('Invitation not sent');
    $response->assertSee(route('settings.team.show', $pam->id));
    $response->assertDontSee('Manage access');
});

it('opens the panel of one person', function () {
    $michael = owner();
    $toby = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Toby', 'last_name' => 'Flenderson', 'display_name' => null, 'email' => 'toby@dundermifflin.com']);

    $response = $this->actingAs($michael)->get(route('settings.team.show', $toby->id));

    $response->assertStatus(200);
    $response->assertSee('Manage access');
    $response->assertSee('Send an invitation');
    $response->assertSee('Prepare it for later');
    $response->assertSee('Permissions');
});

it('tells the only owner why their role is locked', function () {
    $michael = owner();

    $response = $this->actingAs($michael)->get(route('settings.team.show', $michael->staff->id));

    $response->assertSee('You are the only owner of Dunder Mifflin.');
    $response->assertDontSee('Remove the account');
});

it('does not open the panel of a deactivated person', function () {
    $michael = owner();
    $ryan = Staff::factory()->create(['company_id' => $michael->company_id, 'is_active' => false]);

    $this->actingAs($michael)->get(route('settings.team.show', $ryan->id))->assertNotFound();
});

it('does not open the panel of somebody of another company', function () {
    $michael = owner();

    $this->actingAs($michael)->get(route('settings.team.show', Staff::factory()->create()->id))->assertNotFound();
});

it('refuses somebody who may not manage roles', function () {
    $creed = User::factory()->create();
    $staff = Staff::factory()->create(['company_id' => $creed->company_id]);

    $this->actingAs($creed)->get(route('settings.team.index'))->assertNotFound();
    $this->actingAs($creed)->get(route('settings.team.show', $staff->id))->assertNotFound();
});
