<?php

declare(strict_types=1);

use App\Helpers\CatalogueSnapshot;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;

it('keeps a service with its place prices and steps', function () {
    $service = Service::factory()->create(['name' => ['en' => 'Beard trim'], 'prices' => ['CAD' => 2000], 'position' => 3]);

    $snapshot = CatalogueSnapshot::ofService($service);

    expect($snapshot['categories'])->toEqual([]);
    expect($snapshot['services'][0]['name'])->toEqual(['en' => 'Beard trim']);
    expect($snapshot['services'][0]['position'])->toEqual(3);
    expect($snapshot['services'][0]['steps'])->toEqual([['name' => null, 'duration' => 30, 'requires_staff' => true]]);
});

it('keeps the whole catalogue of a company', function () {
    $company = Company::factory()->create();
    $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
    Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id]);
    Service::factory()->create(['company_id' => $company->id]);
    Service::factory()->create();

    $snapshot = CatalogueSnapshot::ofCompany($company);

    expect($snapshot['categories'])->toHaveCount(1);
    expect($snapshot['categories'][0]['services'])->toHaveCount(1);
    expect($snapshot['services'])->toHaveCount(1);
});
