<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue\ServiceControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('adds a service to a category and opens it', function () {
    Queue::fake();

    $michael = owner();
    $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);

    $response = $this->actingAs($michael)->post(route('onboarding.services.services.create', $category));

    $service = Service::query()->where('company_id', $michael->company_id)->sole();

    $response->assertRedirect(route('onboarding.services.services.edit', $service));
    expect($service->service_category_id)->toEqual($category->id);
    expect($service->prices)->toEqual([]);
    expect($service->duration)->toEqual(30);
});

it('shows the service in the panel', function () {
    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage']]);

    $response = $this->actingAs($michael)->get(route('onboarding.services.services.edit', $service));

    $response->assertStatus(200);
    $response->assertSee('service-panel-title', false);
    $response->assertSee('Break it into steps');
});

it('updates a service with its steps', function () {
    Queue::fake();

    $michael = owner(languages: ['en', 'fr'], currencies: ['CAD', 'USD']);
    $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
    $service = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage']]);

    $response = $this->actingAs($michael)->put(route('onboarding.services.services.update', $service), [
        'name' => ['en' => 'Balayage', 'fr' => 'Balayage'],
        'description' => ['en' => 'Sun kissed', 'fr' => ''],
        'category' => $colours->id,
        'prices' => ['CAD' => '180', 'USD' => ''],
        'steps' => [
            ['name' => ['en' => 'Application'], 'duration' => '45', 'requires_staff' => '1'],
            ['name' => ['en' => 'Processing'], 'duration' => '30', 'requires_staff' => '0'],
            ['name' => ['en' => 'Rinse'], 'duration' => '30', 'requires_staff' => '1'],
        ],
    ]);

    $response->assertRedirect(route('onboarding.services.services.edit', $service));

    $service->refresh();

    expect($service->service_category_id)->toEqual($colours->id);
    expect($service->prices)->toEqual(['CAD' => 18000]);
    expect($service->description)->toEqual(['en' => 'Sun kissed']);
    expect($service->duration)->toEqual(105);
    expect($service->free_duration)->toEqual(30);
});

it('refuses a service without a step that needs a team member', function () {
    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);

    $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.services.update', $service), [
        'name' => ['en' => 'Processing only'],
        'steps' => [['duration' => '30', 'requires_staff' => '0']],
    ]);

    $response->assertRedirect(route('onboarding.services.services.edit', $service));
    $response->assertSessionHasErrors('service');
    expect($service->refresh()->steps->first()->requires_staff)->toBeTrue();
});

it('refuses a step shorter than a minute', function () {
    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id]);

    $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.services.update', $service), [
        'steps' => [['duration' => '0', 'requires_staff' => '1']],
    ]);

    $response->assertRedirect(route('onboarding.services.services.edit', $service));
    $response->assertSessionHasErrors('steps.0.duration');
});

it('offers to keep a service without a category', function () {
    $michael = owner();
    ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Hair Coloring']]);
    $service = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => null, 'name' => ['en' => 'Consultation']]);

    $response = $this->actingAs($michael)->get(route('onboarding.services.services.edit', $service));

    $response->assertSee('<option value="" selected>Without a category</option>', false);
});

it('takes a service out of its category', function () {
    Queue::fake();

    $michael = owner();
    $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
    $service = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Balayage']]);

    $this->actingAs($michael)->put(route('onboarding.services.services.update', $service), [
        'name' => ['en' => 'Balayage'],
        'category' => '',
        'steps' => [['duration' => '30', 'requires_staff' => '1']],
    ]);

    expect($service->refresh()->service_category_id)->toBeNull();
});

it('removes a service and keeps it for undo', function () {
    Queue::fake();

    $michael = owner();
    $service = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Root Touch-up']]);

    $response = $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $service));

    $response->assertRedirect(route('onboarding.services.index'));
    $response->assertSessionHas('onboarding.removal.message', '“Root Touch-up” removed.');
    $this->assertModelMissing($service);
});

it('returns not found for a service or a category of another company', function () {
    $michael = owner();
    $service = Service::factory()->create();
    $mine = Service::factory()->create(['company_id' => $michael->company_id]);
    $category = ServiceCategory::factory()->create();

    $this->actingAs($michael)->get(route('onboarding.services.services.edit', $service))->assertNotFound();
    $this->actingAs($michael)->put(route('onboarding.services.services.update', $service), ['steps' => [['duration' => '30', 'requires_staff' => '1']]])->assertNotFound();
    $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $service))->assertNotFound();
    $this->actingAs($michael)->post(route('onboarding.services.services.create', $category))->assertNotFound();
    $this->actingAs($michael)->put(route('onboarding.services.services.update', $mine), ['category' => $category->id, 'steps' => [['duration' => '30', 'requires_staff' => '1']]])->assertNotFound();
    $this->assertModelExists($service);
});

/**
 * @param  list<string>  $languages
 * @param  list<string>  $currencies
 */
function owner(array $languages = ['en'], array $currencies = ['CAD']): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
        'languages' => $languages,
        'currencies' => $currencies,
    ]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
