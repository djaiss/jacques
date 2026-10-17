<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue\ServicePriceControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('sets the prices in cents and removes an empty one', function () {
    Queue::fake();

    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id, 'prices' => ['CAD' => 3500, 'USD' => 2600]]);

    $response = $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), [
        'prices' => ['CAD' => '42,50', 'USD' => ''],
    ]);

    $response->assertRedirect(route('onboarding.services.index'));
    expect($service->refresh()->prices)->toEqual(['CAD' => 4250]);
    expect($service->missingCurrencies())->toEqual(['USD']);
});

it('keeps a free service free', function () {
    Queue::fake();

    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '0', 'USD' => '0']]);

    expect($service->refresh()->prices)->toEqual(['CAD' => 0, 'USD' => 0]);
});

it('refuses a negative price or too many decimals', function () {
    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '-5']])
        ->assertSessionHasErrors('prices.CAD', errorBag: "service_{$service->id}");
    $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '12.345']])
        ->assertSessionHasErrors('prices.CAD', errorBag: "service_{$service->id}");
    expect($service->refresh()->prices)->toEqual(['CAD' => 3500]);
});

it('returns not found for a service of another company', function () {
    $michael = owner();
    $service = Service::factory()->create();

    $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '10']])->assertNotFound();
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
        'currencies' => ['CAD', 'USD'],
    ]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
