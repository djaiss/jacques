<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\TeamRoleControllerTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id]);

    return $michael;
}

function role(User $owner, string $slug): Role
{
    return Role::query()->where('company_id', $owner->company_id)->where('slug', $slug)->firstOrFail();
}

it('gives somebody who signs in another role', function () {
    Queue::fake();

    $michael = owner();
    $account = test()->giveDefaultRole(User::factory()->create(['company_id' => $michael->company_id]), Role::STAFF);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $account->id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);

    $response = $this->actingAs($michael)->put(route('settings.teamRoles.update', $jim->id), [
        'role_id' => role($michael, Role::MANAGER)->id,
    ]);

    $response->assertRedirect(route('settings.team.index'));
    $response->assertSessionHas('status', 'The access of Jim Halpert is saved.');
    expect($account->roles()->pluck('roles.id')->all())->toBe([role($michael, Role::MANAGER)->id]);
});

it('changes the role of a prepared invitation', function () {
    Queue::fake();

    $michael = owner();
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'pam@dundermifflin.com']);
    $invitation = StaffInvitation::factory()->create(['staff_id' => $pam->id, 'role_id' => role($michael, Role::STAFF)->id]);

    $this->actingAs($michael)->put(route('settings.teamRoles.update', $pam->id), [
        'role_id' => role($michael, Role::RECEPTIONIST)->id,
    ]);

    expect($invitation->refresh()->role_id)->toBe(role($michael, Role::RECEPTIONIST)->id);
});

it('keeps the only owner on the owner role', function () {
    $michael = owner();

    $this->actingAs($michael)->put(route('settings.teamRoles.update', $michael->staff->id), [
        'role_id' => role($michael, Role::STAFF)->id,
    ])->assertNotFound();

    expect($michael->isOwner())->toBeTrue();
});

it('has nothing to save for somebody with neither account nor invitation', function () {
    $michael = owner();
    $toby = Staff::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->put(route('settings.teamRoles.update', $toby->id), [
        'role_id' => role($michael, Role::STAFF)->id,
    ])->assertNotFound();
});

it('does not find a role of another company', function () {
    $michael = owner();
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => User::factory()->create(['company_id' => $michael->company_id])->id]);

    $this->actingAs($michael)->put(route('settings.teamRoles.update', $jim->id), [
        'role_id' => Role::factory()->create()->id,
    ])->assertNotFound();
});

it('refuses somebody who may not manage roles', function () {
    $michael = owner();
    $creed = User::factory()->create(['company_id' => $michael->company_id]);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => User::factory()->create(['company_id' => $michael->company_id])->id]);

    $this->actingAs($creed)->put(route('settings.teamRoles.update', $jim->id), [
        'role_id' => role($michael, Role::OWNER)->id,
    ])->assertNotFound();
});
