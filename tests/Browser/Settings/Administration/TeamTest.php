<?php

declare(strict_types=1);

namespace Tests\Browser\Settings\Administration\TeamTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id, 'display_name' => 'Michael Scott', 'email' => 'michael@dundermifflin.com']);

    return $michael;
}

it('moves between the tabs of the team', function () {
    $michael = owner();
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Pam Beesly', 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $pam->id]);
    Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Toby Flenderson']);
    $this->actingAs($michael);

    $page = visit(route('settings.team.index'));

    $page->click('a:has-text("Invited")')
        ->assertPathIs('/settings/administration/team/invited')
        ->assertSee('Pam Beesly')
        ->assertDontSee('Toby Flenderson')
        ->assertDontSee('michael@dundermifflin.com')
        ->click('a:has-text("No account")')
        ->assertPathIs('/settings/administration/team/no-account')
        ->assertSee('Toby Flenderson')
        ->assertDontSee('Pam Beesly')
        ->assertNoJavaScriptErrors();
});

it('opens the panel of a person and switches the permissions with the role', function () {
    $michael = owner();
    Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Toby Flenderson', 'email' => 'toby@dundermifflin.com']);
    $this->actingAs($michael);

    $page = visit(route('settings.team.index'));

    $page->click('a:has-text("Manage Toby Flenderson")')
        ->assertSee('Manage access')
        ->assertSee('Send an invitation')
        ->click('label:has-text("Owner")')
        ->assertSee('Edit this role')
        ->assertNoJavaScriptErrors();
});

it('asks before removing an account and keeps it on cancel', function () {
    $michael = owner();
    $account = User::factory()->create(['company_id' => $michael->company_id]);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $account->id, 'display_name' => 'Jim Halpert']);
    $this->actingAs($michael);

    $page = visit(route('settings.team.show', $jim->id));

    $page->click('button:has-text("Remove the account")')
        ->assertSee('Remove the account of Jim Halpert?')
        ->click('[role="dialog"][aria-labelledby="team-panel-remove-title"] button:has-text("Keep it")')
        ->assertDontSee('Remove the account of Jim Halpert?')
        ->assertNoJavaScriptErrors();

    $this->assertModelExists($account);
});
