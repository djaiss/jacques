<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\TeamSignsInControllerTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;

it('lists only the people who sign in', function () {
    $company = Company::factory()->create();
    $owner = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $owner->id, 'email' => 'michael@dundermifflin.com']);
    $pam = Staff::factory()->create(['company_id' => $company->id, 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->sent()->create(['staff_id' => $pam->id]);
    Staff::factory()->create(['company_id' => $company->id, 'email' => 'toby@dundermifflin.com']);

    $response = $this->actingAs($owner)->get(route('settings.teamSignsIn.index'));

    $response->assertStatus(200);
    $response->assertSee('michael@dundermifflin.com');
    $response->assertDontSee('pam@dundermifflin.com');
    $response->assertDontSee('toby@dundermifflin.com');
});

it('refuses somebody who may not manage roles', function () {
    $this->actingAs(User::factory()->create())->get(route('settings.teamSignsIn.index'))->assertNotFound();
});
