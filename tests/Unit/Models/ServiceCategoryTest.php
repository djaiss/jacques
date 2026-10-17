<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;

it('belongs to a company', function () {
    $company = Company::factory()->create();
    $category = ServiceCategory::factory()->create(['company_id' => $company->id]);

    expect($category->company()->exists())->toBeTrue();
    expect($category->company->id)->toEqual($company->id);
});

it('has many services in the order of their position', function () {
    $category = ServiceCategory::factory()->create();
    $colour = Service::factory()->create(['company_id' => $category->company_id, 'service_category_id' => $category->id, 'position' => 2]);
    $haircut = Service::factory()->create(['company_id' => $category->company_id, 'service_category_id' => $category->id, 'position' => 1]);

    expect($category->services()->exists())->toBeTrue();
    expect($category->services->pluck('id')->all())->toEqual([$haircut->id, $colour->id]);
});

it('reads its name in the language of the interface', function () {
    $company = Company::factory()->create(['languages' => ['fr', 'en']]);
    $cuts = ServiceCategory::factory()->create([
        'company_id' => $company->id,
        'name' => ['fr' => 'Coupes', 'en' => 'Cuts'],
    ]);

    expect($cuts->localizedName('en'))->toEqual('Cuts');
    expect($cuts->localizedName('de_DE'))->toEqual('Coupes');
});

it('lists the languages its name is missing in', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr', 'de']]);
    $cuts = ServiceCategory::factory()->create([
        'company_id' => $company->id,
        'name' => ['en' => 'Cuts', 'es' => 'Algo'],
    ]);

    expect($cuts->missingLanguages())->toEqual(['fr', 'de']);
});
