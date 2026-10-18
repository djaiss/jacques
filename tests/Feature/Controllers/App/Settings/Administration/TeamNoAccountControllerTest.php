<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\TeamNoAccountControllerTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;

it('lists only the people without an account', function () {
    $company = Company::factory()->create();
    $owner = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $owner->id, 'email' => 'michael@dundermifflin.com']);
    $pam = Staff::factory()->create(['company_id' => $company->id, 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->sent()->create(['staff_id' => $pam->id]);
    Staff::factory()->create(['company_id' => $company->id, 'email' => 'toby@dundermifflin.com']);

    $response = $this->actingAs($owner)->get(route('settings.teamNoAccount.index'));

    $response->assertStatus(200);
    $response->assertSee('toby@dundermifflin.com');
    $response->assertDontSee('michael@dundermifflin.com');
    $response->assertDontSee('pam@dundermifflin.com');
});

it('says when nobody is on the tab', function () {
    $owner = test()->giveDefaultRole(User::factory()->create(), Role::OWNER);
    Staff::factory()->create(['company_id' => $owner->company_id, 'user_id' => $owner->id]);

    $this->actingAs($owner)->get(route('settings.teamNoAccount.index'))->assertSee('Nobody matches this filter right now.');
});

it('refuses somebody who may not manage roles', function () {
    $this->actingAs(User::factory()->create())->get(route('settings.teamNoAccount.index'))->assertNotFound();
});
