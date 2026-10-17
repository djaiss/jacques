<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\CreateDefaultCatalogueTest;

use App\Actions\CreateDefaultCatalogue;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;

it('creates the categories in order', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();

    $categories = ServiceCategory::query()
        ->where('company_id', $company->id)
        ->orderBy('position')
        ->get()
        ->map(fn (ServiceCategory $category): string => $category->name['en'])
        ->all();

    expect($categories)->toEqual(['Haircuts', 'Hair Coloring', 'Hair Styling', 'Hair Treatments', 'Barbering', 'Other Services']);
});

it('creates the services in order with their prices and durations', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();

    $services = Service::query()
        ->where('services.company_id', $company->id)
        ->join('service_categories', 'service_categories.id', '=', 'services.service_category_id')
        ->orderBy('service_categories.position')
        ->orderBy('services.position')
        ->select('services.*')
        ->with('steps')
        ->get()
        ->map(fn (Service $service): array => [$service->name['en'], $service->prices['CAD'], $service->duration, $service->is_active])
        ->all();

    expect($services)->toEqual([
        ["Men's Haircut", 3500, 30, true],
        ["Women's Haircut", 6500, 60, true],
        ["Kids' Haircut", 2500, 30, true],
        ['Full Color', 10000, 90, true],
        ['Root Touch-up', 8500, 90, true],
        ['Balayage', 18000, 150, true],
        ['Highlights', 14000, 105, true],
        ['Blowout', 4500, 45, true],
        ['Updo', 7500, 60, true],
        ['Hair Straightening', 10000, 90, true],
        ['Deep Conditioning', 4000, 35, true],
        ['Keratin Treatment', 15000, 120, true],
        ['Scalp Treatment', 5500, 45, true],
        ['Beard Trim', 2000, 20, true],
        ['Traditional Shave', 3500, 30, true],
        ['Hair & Beard', 5000, 50, true],
        ['Consultation', 0, 15, true],
    ]);
});

it('prices every service with the same amount in every offered currency', function () {
    $company = Company::factory()->create(['currencies' => ['CAD']]);

    new CreateDefaultCatalogue(company: $company)->execute();

    $haircut = Service::query()->where('company_id', $company->id)->where('name->en', "Women's Haircut")->firstOrFail();

    expect($haircut->prices)->toEqual(['CAD' => 6500, 'USD' => 6500, 'EUR' => 6500, 'GBP' => 6500, 'CHF' => 6500]);
    expect($haircut->missingCurrencies())->toEqual([]);
});

it('leaves no service without a price when the salon switches to another currency', function () {
    $company = Company::factory()->create(['currencies' => ['CAD']]);

    new CreateDefaultCatalogue(company: $company)->execute();

    $company->update(['currencies' => ['EUR', 'CHF']]);

    $services = Service::query()->where('company_id', $company->id)->get();

    foreach ($services as $service) {
        expect($service->missingCurrencies())->toEqual([]);
    }

    expect($services->firstOrFail(fn (Service $service): bool => $service->name['en'] === "Men's Haircut")->prices['EUR'])->toEqual(3500);
});

it('splits three services into steps with processing time', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();

    expect(ServiceStep::query()->whereIn('service_id', Service::query()->where('company_id', $company->id)->select('id'))->count())->toEqual(23);
    expect(steps($company, 'Full Color'))->toEqual([['Color application', 30, true], ['Processing', 30, false], ['Rinse & finish', 30, true]]);
    expect(steps($company, 'Highlights'))->toEqual([['Application', 45, true], ['Processing', 30, false], ['Rinse & finish', 30, true]]);
    expect(steps($company, 'Deep Conditioning'))->toEqual([['Application', 10, true], ['Processing', 15, false], ['Rinse & finish', 10, true]]);
    expect(steps($company, "Men's Haircut"))->toEqual([[null, 30, true]]);
});

it('names the steps in every interface locale', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();

    $processing = ServiceStep::query()
        ->whereIn('service_id', Service::query()->where('company_id', $company->id)->where('name->en', 'Full Color')->select('id'))
        ->where('position', 2)
        ->firstOrFail();

    expect(array_keys($processing->name))->toEqualCanonicalizing(['en', 'fr', 'de', 'es']);
    expect($processing->name['en'])->toEqual('Processing');
});

it('names every category and service in every interface locale', function () {
    $company = Company::factory()->create(['languages' => ['en', 'pt']]);

    new CreateDefaultCatalogue(company: $company)->execute();

    $names = [
        ...ServiceCategory::query()->where('company_id', $company->id)->pluck('name')->all(),
        ...Service::query()->where('company_id', $company->id)->pluck('name')->all(),
    ];

    expect($names)->toHaveCount(23);

    foreach ($names as $name) {
        expect(array_keys($name))->toEqualCanonicalizing(['en', 'fr', 'de', 'es']);
        expect($name)->not->toContain('');
    }

    $haircut = Service::query()->where('company_id', $company->id)->where('name->en', "Women's Haircut")->firstOrFail();

    expect($haircut->name['fr'])->toEqual('Coupe femme');
    expect($haircut->name['de'])->toEqual('Damenhaarschnitt');
    expect($haircut->name['es'])->toEqual('Corte de dama');
});

it('records when the catalogue was created', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();

    expect($company->refresh()->default_catalogue_created_at?->timestamp)->toEqualWithDelta(now()->timestamp, 5);
});

it('creates nothing the second time', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();
    new CreateDefaultCatalogue(company: $company)->execute();

    expect(ServiceCategory::query()->where('company_id', $company->id)->count())->toEqual(6);
    expect(Service::query()->where('company_id', $company->id)->count())->toEqual(17);
});

it('keeps what the owner deleted or edited when it runs again', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();

    Service::query()->where('company_id', $company->id)->where('name->en', 'Balayage')->delete();
    Service::query()->where('company_id', $company->id)->where('name->en', 'Blowout')->update(['prices' => json_encode(['CAD' => 5000])]);

    new CreateDefaultCatalogue(company: $company)->execute();

    expect(Service::query()->where('company_id', $company->id)->where('name->en', 'Balayage')->exists())->toBeFalse();
    expect(Service::query()->where('company_id', $company->id)->where('name->en', 'Blowout')->first()->prices)->toEqual(['CAD' => 5000]);
    expect(Service::query()->where('company_id', $company->id)->count())->toEqual(16);
});

it('offers the services at the headquarters', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id, 'is_headquarters' => true]);
    $stamford = Location::factory()->create(['company_id' => $company->id, 'is_headquarters' => false]);

    new CreateDefaultCatalogue(company: $company)->execute();

    expect(LocationService::query()->where('location_id', $scranton->id)->whereNull('prices')->where('is_active', true)->count())->toEqual(17);
    expect(LocationService::query()->where('location_id', $stamford->id)->count())->toEqual(0);
});

it('offers nothing without a headquarters', function () {
    $company = Company::factory()->create();

    new CreateDefaultCatalogue(company: $company)->execute();

    expect(LocationService::query()->count())->toEqual(0);
});

it('leaves another company untouched', function () {
    $dunderMifflin = Company::factory()->create();
    $vance = Company::factory()->create();
    Location::factory()->create(['company_id' => $vance->id, 'is_headquarters' => true]);

    new CreateDefaultCatalogue(company: $dunderMifflin)->execute();

    expect(ServiceCategory::query()->where('company_id', $vance->id)->count())->toEqual(0);
    expect(Service::query()->where('company_id', $vance->id)->count())->toEqual(0);
    expect(LocationService::query()->count())->toEqual(0);
    expect($vance->refresh()->default_catalogue_created_at)->toBeNull();
});

/** @return list<array{int, int}> */
function steps(Company $company, string $name): array
{
    return ServiceStep::query()
        ->whereIn('service_id', Service::query()->where('company_id', $company->id)->where('name->en', $name)->select('id'))
        ->orderBy('position')
        ->get()
        ->map(fn (ServiceStep $step): array => [$step->name['en'] ?? null, $step->duration, $step->requires_staff])
        ->all();
}
