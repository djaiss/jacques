<?php

declare(strict_types=1);

use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\Staff;

it('belongs to a location', function () {
    $location = Location::factory()->create();
    $locationService = LocationService::factory()->create(['location_id' => $location->id]);

    expect($locationService->location()->exists())->toBeTrue();
    expect($locationService->location->id)->toEqual($location->id);
});

it('belongs to a service of the same company', function () {
    $locationService = LocationService::factory()->create();

    expect($locationService->service()->exists())->toBeTrue();
    expect($locationService->service->company_id)->toEqual($locationService->location->company_id);
});

it('belongs to many staff members', function () {
    $locationService = LocationService::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
    $locationService->staff()->attach($jim->id);

    expect($locationService->staff()->exists())->toBeTrue();
    expect($locationService->staff->first()->id)->toEqual($jim->id);
});

it('charges its own price in the currencies it replaces', function () {
    $location = Location::factory()->create();
    $haircut = Service::factory()->create(['company_id' => $location->company_id, 'prices' => ['CAD' => 3500, 'USD' => 2600]]);
    $locationService = LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $haircut->id, 'prices' => ['USD' => 3000]]);

    expect($locationService->resolvedPrice('USD'))->toEqual(3000);
    expect($locationService->resolvedPrice('CAD'))->toEqual(3500);
});

it('follows the price of the service without one', function () {
    $location = Location::factory()->create();
    $haircut = Service::factory()->create(['company_id' => $location->company_id, 'prices' => ['CAD' => 3500]]);
    $locationService = LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $haircut->id]);

    $haircut->update(['prices' => ['CAD' => 3800]]);

    expect($locationService->refresh()->resolvedPrice('CAD'))->toEqual(3800);
});

it('has no price in a currency neither it nor the service has', function () {
    $location = Location::factory()->create();
    $haircut = Service::factory()->create(['company_id' => $location->company_id, 'prices' => ['CAD' => 3500]]);
    $locationService = LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $haircut->id]);

    expect($locationService->resolvedPrice('EUR'))->toBeNull();
});
