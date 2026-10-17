<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue\CategoryControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('adds an unnamed category', function () {
    Queue::fake();

    $michael = owner();

    $response = $this->actingAs($michael)->post(route('onboarding.services.categories.create'));

    $response->assertRedirect(route('onboarding.services.index'));
    $response->assertSessionHas('catalogue_saved', true);

    expect(ServiceCategory::query()->where('company_id', $michael->company_id)->sole()->name)->toEqual([]);
});

it('renames a category in every language', function () {
    Queue::fake();

    $michael = owner(languages: ['en', 'fr']);
    $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts']]);

    $response = $this->actingAs($michael)->put(route('onboarding.services.categories.update', $category), [
        'name' => ['en' => 'Haircuts', 'fr' => 'Coupes'],
    ]);

    $response->assertRedirect(route('onboarding.services.index'));
    expect($category->refresh()->name)->toEqual(['en' => 'Haircuts', 'fr' => 'Coupes']);
});

it('refuses a name another category has', function () {
    $michael = owner();
    ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts'], 'position' => 1]);
    $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Colours'], 'position' => 2]);

    $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.categories.update', $colours), [
        'name' => ['en' => 'Cuts'],
    ]);

    $response->assertSessionHasErrors('category', errorBag: "category_{$colours->id}");
    expect($colours->refresh()->name)->toEqual(['en' => 'Colours']);
});

it('removes a category with its services and keeps it for undo', function () {
    Queue::fake();

    $michael = owner();
    $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Barbering']]);
    $shave = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $category->id]);

    $response = $this->actingAs($michael)->delete(route('onboarding.services.categories.destroy', $category));

    $response->assertRedirect(route('onboarding.services.index'));
    $response->assertSessionHas('onboarding.removal.message', '“Barbering” and its 1 service removed.');
    $this->assertModelMissing($category);
    $this->assertModelMissing($shave);
});

it('returns not found for a category of another company', function () {
    $michael = owner();
    $category = ServiceCategory::factory()->create();

    $this->actingAs($michael)->put(route('onboarding.services.categories.update', $category), ['name' => ['en' => 'Cuts']])->assertNotFound();
    $this->actingAs($michael)->delete(route('onboarding.services.categories.destroy', $category))->assertNotFound();
    $this->assertModelExists($category);
});

/**
 * @param  list<string>  $languages
 */
function owner(array $languages = ['en']): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
        'languages' => $languages,
    ]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
