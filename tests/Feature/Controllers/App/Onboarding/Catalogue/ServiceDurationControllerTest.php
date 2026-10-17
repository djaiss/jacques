<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue\ServiceDurationControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('changes the duration of a one step service', function () {
    Queue::fake();

    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);

    $response = $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => '45']);

    $response->assertRedirect(route('onboarding.services.index'));
    $response->assertSessionHas('catalogue_saved', true);
    expect($service->refresh()->duration)->toEqual(45);
});

it('refuses a duration that is not a number', function () {
    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);

    $response = $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => 'long']);

    $response->assertSessionHasErrors('duration', errorBag: "service_{$service->id}");
    expect($service->refresh()->duration)->toEqual(30);
});

it('refuses to change the duration of a service made of steps', function () {
    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);
    ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2, 'requires_staff' => false]);

    $response = $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => '45']);

    $response->assertSessionHasErrors('service', errorBag: "service_{$service->id}");
    expect($service->refresh()->duration)->toEqual(60);
});

it('returns not found for a service of another company', function () {
    $michael = owner();
    $service = Service::factory()->create();

    $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => '45'])->assertNotFound();
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
    ]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
