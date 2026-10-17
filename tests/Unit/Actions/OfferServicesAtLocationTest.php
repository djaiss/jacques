<?php

declare(strict_types=1);

use App\Actions\OfferServicesAtLocation;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;

it('offers every active service at the price of the service', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->create(['company_id' => $company->id]);
    $beardTrim = Service::factory()->create(['company_id' => $company->id]);

    $offered = new OfferServicesAtLocation(location: $scranton)->execute();

    expect($offered->pluck('service_id')->all())->toEqualCanonicalizing([$haircut->id, $beardTrim->id]);
    expect(LocationService::query()->where('location_id', $scranton->id)->count())->toEqual(2);
    $this->assertDatabaseHas('location_services', [
        'location_id' => $scranton->id,
        'service_id' => $haircut->id,
        'prices' => null,
        'is_active' => true,
    ]);
    $this->assertDatabaseHas('location_services', [
        'location_id' => $scranton->id,
        'service_id' => $beardTrim->id,
    ]);
});

it('skips a deactivated service', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $balayage = Service::factory()->create(['company_id' => $company->id, 'is_active' => false]);

    new OfferServicesAtLocation(location: $scranton)->execute();

    $this->assertDatabaseMissing('location_services', ['service_id' => $balayage->id]);
});

it('does not offer a service twice', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->create(['company_id' => $company->id]);
    LocationService::factory()->create([
        'location_id' => $scranton->id,
        'service_id' => $haircut->id,
        'prices' => ['CAD' => 4000],
    ]);

    $offered = new OfferServicesAtLocation(location: $scranton)->execute();

    expect($offered->isEmpty())->toBeTrue();
    expect(LocationService::query()->where('location_id', $scranton->id)->count())->toEqual(1);
    expect(LocationService::query()->where('location_id', $scranton->id)->first()->prices)->toEqual(['CAD' => 4000]);
});

it('never offers a service of another company', function () {
    $dunderMifflin = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $dunderMifflin->id]);
    $vance = Company::factory()->create();
    Service::factory()->create(['company_id' => $vance->id]);

    new OfferServicesAtLocation(location: $scranton)->execute();

    expect(LocationService::query()->count())->toEqual(0);
});
