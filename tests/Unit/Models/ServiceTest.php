<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;

it('belongs to a company', function () {
    $company = Company::factory()->create();
    $service = Service::factory()->create(['company_id' => $company->id]);

    expect($service->company()->exists())->toBeTrue();
    expect($service->company->id)->toEqual($company->id);
});

it('belongs to a category', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['company_id' => $category->company_id, 'service_category_id' => $category->id]);

    expect($service->category()->exists())->toBeTrue();
    expect($service->category->id)->toEqual($category->id);
});

it('has no category by default', function () {
    $service = Service::factory()->create();

    expect($service->category)->toBeNull();
});

it('has many steps in the order they are performed', function () {
    $service = Service::factory()->create();
    $second = ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2]);

    expect($service->steps()->exists())->toBeTrue();
    expect($service->steps->pluck('position')->all())->toEqual([1, 2]);
    expect($service->steps->last()->id)->toEqual($second->id);
});

it('has many location services', function () {
    $locationService = LocationService::factory()->create();

    expect($locationService->service->locationServices()->exists())->toBeTrue();
});

it('belongs to many locations', function () {
    $location = Location::factory()->create();
    $service = Service::factory()->create(['company_id' => $location->company_id]);
    $service->locations()->attach($location->id, ['prices' => json_encode(['CAD' => 4000])]);

    expect($service->locations()->exists())->toBeTrue();
    expect(json_decode($service->locations->first()->pivot->prices, true))->toEqual(['CAD' => 4000]);
});

it('lasts the time of its steps split into busy and free time', function () {
    $service = Service::factory()->create();
    $service->steps()->delete();
    ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 1, 'duration' => 20, 'requires_staff' => true]);
    ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2, 'duration' => 30, 'requires_staff' => false]);
    ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 3, 'duration' => 15, 'requires_staff' => true]);

    $service->refresh();

    expect($service->duration)->toEqual(65);
    expect($service->busy_duration)->toEqual(35);
    expect($service->free_duration)->toEqual(30);
});

it('casts the prices and the active flag', function () {
    $service = Service::factory()->inactive()->create(['prices' => ['CAD' => 3500, 'USD' => 2600]]);

    expect($service->refresh()->prices)->toBe(['CAD' => 3500, 'USD' => 2600]);
    expect($service->is_active)->toBeFalse();
});

it('reads its name in the language of the interface', function () {
    $company = Company::factory()->create(['languages' => ['fr', 'en']]);
    $haircut = Service::factory()->create([
        'company_id' => $company->id,
        'name' => ['fr' => 'Coupe femme', 'en' => "Women's haircut"],
    ]);

    expect($haircut->localizedName('en'))->toEqual("Women's haircut");
    expect($haircut->localizedName('de_DE'))->toEqual('Coupe femme');
});

it('lists the currencies it has no price in', function () {
    $company = Company::factory()->create(['currencies' => ['CAD', 'USD', 'EUR']]);
    $haircut = Service::factory()->create([
        'company_id' => $company->id,
        'prices' => ['CAD' => 6500, 'GBP' => 3800],
    ]);

    expect($haircut->missingCurrencies())->toEqual(['USD', 'EUR']);
});

it('lists the languages its name is missing in', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr', 'de']]);
    $haircut = Service::factory()->create([
        'company_id' => $company->id,
        'name' => ['en' => "Women's haircut", 'es' => 'Algo'],
    ]);

    expect($haircut->missingLanguages())->toEqual(['fr', 'de']);
});

it('lists the languages missing from its description and step names', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr']]);
    $colour = Service::factory()->create([
        'company_id' => $company->id,
        'name' => ['en' => 'Full Color', 'fr' => 'Coloration'],
        'description' => ['en' => 'Roots and lengths'],
    ]);

    expect($colour->missingLanguages())->toEqual(['fr']);

    $colour->update(['description' => null]);
    $colour->steps()->delete();
    ServiceStep::factory()->create(['service_id' => $colour->id, 'position' => 1, 'name' => ['en' => 'Application', 'fr' => 'Application']]);
    ServiceStep::factory()->create(['service_id' => $colour->id, 'position' => 2, 'name' => ['en' => 'Processing'], 'requires_staff' => false]);

    expect($colour->refresh()->missingLanguages())->toEqual(['fr']);
    expect($colour->translatableTexts())->toHaveCount(3);
});

it('ignores the name of a single step', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr']]);
    $haircut = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut', 'fr' => 'Coupe'], 'description' => null]);

    expect($haircut->missingLanguages())->toEqual([]);
    expect($haircut->translatableTexts())->toHaveCount(1);
});

it('is bookable in a currency only with a price in it and a name in the first language', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr'], 'currencies' => ['CAD', 'USD']]);
    $haircut = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut'], 'prices' => ['CAD' => 0]]);
    $untitled = Service::factory()->create(['company_id' => $company->id, 'name' => ['fr' => 'Coupe'], 'prices' => ['CAD' => 3500]]);

    expect($haircut->isBookableIn('CAD'))->toBeTrue();
    expect($haircut->isBookableIn('USD'))->toBeFalse();
    expect($untitled->isBookableIn('CAD'))->toBeFalse();
});
