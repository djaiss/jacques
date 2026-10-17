<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue\CatalogueControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('empties the catalogue and keeps it for undo', function () {
    Queue::fake();

    $michael = owner();
    $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
    Service::factory()->count(2)->create(['company_id' => $michael->company_id, 'service_category_id' => $category->id]);

    $response = $this->actingAs($michael)->delete(route('onboarding.services.catalogue.destroy'));

    $response->assertRedirect(route('onboarding.services.index'));
    $response->assertSessionHas('onboarding.removal.message', 'The catalog was emptied.');
    expect(Service::query()->where('company_id', $michael->company_id)->count())->toEqual(0);
    expect(ServiceCategory::query()->where('company_id', $michael->company_id)->count())->toEqual(0);
});

it('brings back the example catalogue into an empty one', function () {
    Queue::fake();

    $michael = owner();

    $response = $this->actingAs($michael)->post(route('onboarding.services.catalogue.create'));

    $response->assertRedirect(route('onboarding.services.index'));
    expect(Service::query()->where('company_id', $michael->company_id)->count())->toEqual(17);
});

it('refuses the example catalogue when the catalogue is not empty', function () {
    $michael = owner();
    Service::factory()->create(['company_id' => $michael->company_id]);

    $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->post(route('onboarding.services.catalogue.create'));

    $response->assertSessionHas('error');
    expect(Service::query()->where('company_id', $michael->company_id)->count())->toEqual(1);
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
    ]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
