<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue\CategoryPositionControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('moves a category up and down', function () {
    Queue::fake();

    $michael = owner();
    $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 1]);
    $barbering = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 2]);

    $response = $this->actingAs($michael)->put(route('onboarding.services.categoryPositions.update', $barbering), ['direction' => 'up']);

    $response->assertRedirect(route('onboarding.services.index'));
    expect($barbering->refresh()->position)->toEqual(1);
    expect($cuts->refresh()->position)->toEqual(2);

    $this->actingAs($michael)->put(route('onboarding.services.categoryPositions.update', $barbering), ['direction' => 'down']);

    expect($barbering->refresh()->position)->toEqual(2);
});

it('refuses to move the first category up', function () {
    $michael = owner();
    $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 1]);

    $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.categoryPositions.update', $cuts), ['direction' => 'up']);

    $response->assertSessionHas('error');
    expect($cuts->refresh()->position)->toEqual(1);
});

it('returns not found for a category of another company', function () {
    $michael = owner();
    $category = ServiceCategory::factory()->create(['position' => 2]);

    $this->actingAs($michael)->put(route('onboarding.services.categoryPositions.update', $category), ['direction' => 'up'])->assertNotFound();
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
    ]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
