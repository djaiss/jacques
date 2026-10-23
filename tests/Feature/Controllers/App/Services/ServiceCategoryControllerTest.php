<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Services\ServiceCategoryControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(array $languages = ['en']): User
{
    $company = Company::factory()->create(['languages' => $languages, 'currencies' => ['CAD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('opens the page to add a category', function () {
    $michael = owner();

    $response = $this->actingAs($michael)->get(route('serviceCategories.new'));

    $response->assertStatus(200);
    $response->assertSee('Add this category');
});

it('adds a category last', function () {
    Queue::fake();

    $michael = owner(languages: ['en', 'fr']);
    ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 1]);

    $response = $this->actingAs($michael)->post(route('serviceCategories.create'), [
        'name' => ['en' => 'Barbering', 'fr' => 'Barbier'],
    ]);

    $response->assertRedirect(route('services.index'));
    $response->assertSessionHas('status', 'The category is added.');
    $category = ServiceCategory::query()->where('company_id', $michael->company_id)->orderByDesc('position')->first();
    expect($category->name)->toEqual(['en' => 'Barbering', 'fr' => 'Barbier']);
    expect($category->position)->toBe(2);
});

it('refuses a category without a name in the first language', function () {
    $michael = owner();

    $response = $this->actingAs($michael)->post(route('serviceCategories.create'), ['name' => ['en' => '']]);

    $response->assertSessionHasErrors('name.en');
    $this->assertDatabaseCount('service_categories', 0);
});

it('renames a category and keeps its description', function () {
    Queue::fake();

    $michael = owner();
    $makeup = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Makeup'], 'description' => ['en' => 'For events']]);

    $response = $this->actingAs($michael)->put(route('serviceCategories.update', $makeup->id), ['name' => ['en' => 'Make-up']]);

    $response->assertRedirect(route('services.index'));
    $makeup->refresh();
    expect($makeup->name)->toEqual(['en' => 'Make-up']);
    expect($makeup->description)->toEqual(['en' => 'For events']);
});

it('opens the page to edit a category with what removing it does', function () {
    $michael = owner();
    $makeup = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Makeup']]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $makeup->id, 'name' => ['en' => 'Event Makeup'], 'position' => 1]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $makeup->id, 'name' => ['en' => 'Bridal Makeup'], 'position' => 2]);

    $response = $this->actingAs($michael)->get(route('serviceCategories.edit', $makeup->id));

    $response->assertStatus(200);
    $response->assertSee('2 services stay in the catalog, without a category: Event Makeup, Bridal Makeup.');
});

it('removes a category and keeps its services', function () {
    Queue::fake();

    $michael = owner();
    $makeup = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
    $bridal = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $makeup->id]);

    $response = $this->actingAs($michael)->delete(route('serviceCategories.destroy', $makeup->id));

    $response->assertRedirect(route('services.index'));
    $response->assertSessionHas('status', 'The category is removed.');
    $this->assertModelMissing($makeup);
    expect($bridal->refresh()->service_category_id)->toBeNull();
});

it('returns not found for a category of another company', function () {
    $michael = owner();
    $elsewhere = ServiceCategory::factory()->create();

    $this->actingAs($michael)->get(route('serviceCategories.edit', $elsewhere->id))->assertNotFound();
    $this->actingAs($michael)->put(route('serviceCategories.update', $elsewhere->id), ['name' => ['en' => 'X']])->assertNotFound();
    $this->actingAs($michael)->delete(route('serviceCategories.destroy', $elsewhere->id))->assertNotFound();
});

it('refuses somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);

    $this->actingAs($dwight)->get(route('serviceCategories.new'))->assertNotFound();
    $this->actingAs($dwight)->post(route('serviceCategories.create'), ['name' => ['en' => 'X']])->assertNotFound();
});
