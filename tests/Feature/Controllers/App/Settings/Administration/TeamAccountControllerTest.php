<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\TeamAccountControllerTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(): User
{
    $company = Company::factory()->create();
    $michael = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id]);

    return $michael;
}

it('removes the account and keeps the person on the team', function () {
    Queue::fake();

    $michael = owner();
    $account = User::factory()->create(['company_id' => $michael->company_id]);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $account->id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null, 'is_bookable' => true]);

    $response = $this->actingAs($michael)->delete(route('settings.teamAccounts.destroy', $jim->id));

    $response->assertRedirect(route('settings.team.show', $jim->id));
    $response->assertSessionHas('status', 'The account is removed.');
    $this->assertModelMissing($account);
    expect($jim->refresh()->user_id)->toBeNull();
    expect($jim->is_bookable)->toBeTrue();
});

it('never removes the account of the person asking', function () {
    $michael = owner();

    $this->actingAs($michael)->delete(route('settings.teamAccounts.destroy', $michael->staff->id))->assertNotFound();

    $this->assertModelExists($michael);
});

it('finds nothing to remove for somebody without an account', function () {
    $michael = owner();
    $toby = Staff::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->delete(route('settings.teamAccounts.destroy', $toby->id))->assertNotFound();
});

it('refuses somebody who is not an owner', function () {
    $michael = owner();
    $dwight = test()->giveDefaultRole(User::factory()->create(['company_id' => $michael->company_id]), Role::MANAGER);
    $account = User::factory()->create(['company_id' => $michael->company_id]);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $account->id]);

    $this->actingAs($dwight)->delete(route('settings.teamAccounts.destroy', $jim->id))->assertNotFound();

    $this->assertModelExists($account);
});

it('does not find somebody of another company', function () {
    $michael = owner();
    $stranger = Staff::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs($michael)->delete(route('settings.teamAccounts.destroy', $stranger->id))->assertNotFound();
});
