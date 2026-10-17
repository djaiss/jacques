<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue\RestorationControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('puts back a removed service in its place', function () {
    Queue::fake();

    $michael = owner();
    $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Full Color'], 'position' => 1]);
    $touchUp = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Root Touch-up'], 'position' => 2]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Balayage'], 'position' => 3]);

    $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $touchUp));
    $response = $this->actingAs($michael)->post(route('onboarding.services.restoration.create'));

    $response->assertRedirect(route('onboarding.services.index'));
    $response->assertSessionMissing('onboarding.removal');
    expect($colours->services()->get()->map(fn (Service $service): string => $service->name['en'])->all())->toEqual(['Full Color', 'Root Touch-up', 'Balayage']);
});

it('puts back an emptied catalogue', function () {
    Queue::fake();

    $michael = owner();
    $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts']]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $category->id]);

    $this->actingAs($michael)->delete(route('onboarding.services.catalogue.destroy'));
    $this->actingAs($michael)->post(route('onboarding.services.restoration.create'));

    expect(ServiceCategory::query()->where('company_id', $michael->company_id)->sole()->name)->toEqual(['en' => 'Cuts']);
    expect(Service::query()->where('company_id', $michael->company_id)->count())->toEqual(1);
});

it('forgets the removal after another change', function () {
    Queue::fake();

    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $service));
    $this->actingAs($michael)->post(route('onboarding.services.categories.create'));
    $response = $this->actingAs($michael)->post(route('onboarding.services.restoration.create'));

    $response->assertSessionHas('error');
    expect(Service::query()->where('company_id', $michael->company_id)->count())->toEqual(0);
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
    ]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
