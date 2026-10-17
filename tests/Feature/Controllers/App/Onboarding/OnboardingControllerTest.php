<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\OnboardingControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;

it('sends the owner to the step they reached', function () {
    $user = owner(OnboardingStepEnum::Staff);

    $this->actingAs($user)->get(route('onboarding.index'))->assertRedirect(route('onboarding.staff.index'));
});

it('starts a new company on the first step', function () {
    $user = owner(OnboardingStepEnum::Location);

    $this->actingAs($user)->get(route('onboarding.index'))->assertRedirect(route('onboarding.location.index'));
});

it('sends a step beyond the one reached back to it', function () {
    $user = owner(OnboardingStepEnum::Services);

    $this->actingAs($user)->get(route('onboarding.availability.index'))->assertRedirect(route('onboarding.services.index'));
    $this->actingAs($user)->put(route('onboarding.staff.update'))->assertRedirect(route('onboarding.services.index'));
});

it('lets the owner revisit a step already done', function () {
    $user = owner(OnboardingStepEnum::Staff);

    $this->actingAs($user)->get(route('onboarding.location.index'))->assertStatus(200);
});

it('leaves the owner free to use the application', function () {
    $user = owner(OnboardingStepEnum::Services);

    $this->actingAs($user)->get(route('settings.index'))->assertStatus(200);
    expect($user->company->refresh()->onboarding_step)->toEqual(OnboardingStepEnum::Services);
});

it('sends a finished company home', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $this->actingAs($user)->get(route('onboarding.index'))->assertRedirect(route('home.index'));
});

it('sends a user who is not the owner home', function () {
    $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
    $jim = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    $this->actingAs($jim)->get(route('onboarding.index'))->assertRedirect(route('home.index'));
});

function owner(OnboardingStepEnum $reached): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => $reached,
    ]);
    $user = User::factory()->create(['company_id' => $company->id]);

    return test()->giveDefaultRole($user, Role::OWNER);
}
