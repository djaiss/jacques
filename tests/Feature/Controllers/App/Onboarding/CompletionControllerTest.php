<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;

it('shows the invitations ready to send', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = User::factory()->create(['company_id' => $company->id]);
    StaffInvitation::factory()->create(['staff_id' => Staff::factory()->create(['company_id' => $company->id])->id]);
    StaffInvitation::factory()->create(['staff_id' => Staff::factory()->create(['company_id' => $company->id])->id]);

    $response = $this->actingAs($michael)->get(route('onboarding.completion.index'));

    $response->assertStatus(200);
    $response->assertSee('Invite your team');
    $response->assertSee('2 invitations are ready to send.');
});

it('hides the section when nothing was prepared', function () {
    $michael = User::factory()->create();

    $this->actingAs($michael)
        ->get(route('onboarding.completion.index'))
        ->assertStatus(200)
        ->assertDontSee('Invite your team');
});

it('is not found while the onboarding is pending', function () {
    $company = Company::factory()->onboardingPending()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($michael)->get(route('onboarding.completion.index'))->assertNotFound();
});
