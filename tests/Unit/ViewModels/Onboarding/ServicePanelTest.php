<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\ViewModels\Onboarding\ServicePanel;

it('prepares a service for the panel', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr'], 'currencies' => ['CAD', 'USD']]);
    $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Hair Coloring']]);
    $colour = Service::factory()->create([
        'company_id' => $company->id,
        'service_category_id' => $colours->id,
        'name' => ['en' => 'Full Color'],
        'description' => null,
        'prices' => ['CAD' => 10000],
    ]);
    ServiceStep::factory()->create(['service_id' => $colour->id, 'position' => 2, 'name' => ['en' => 'Processing'], 'duration' => 15, 'requires_staff' => false]);

    $panel = new ServicePanel(
        service: $colour->load('steps'),
        categories: ServiceCategory::query()->where('company_id', $company->id)->get(),
        languages: ['en', 'fr'],
        currencies: [['code' => 'CAD', 'symbol' => 'CA$', 'name' => 'Canadian dollar'], ['code' => 'USD', 'symbol' => 'US$', 'name' => 'US dollar']],
    );

    expect($panel->title())->toEqual('Full Color');
    expect($panel->eyebrow())->toEqual('Edit service · Hair Coloring');
    expect($panel->names())->toEqual(['en' => 'Full Color', 'fr' => '']);
    expect($panel->priceStatus())->toEqual('1 of 2 missing');
    expect($panel->hasEveryPrice())->toBeFalse();
    expect($panel->prices()[0]['amount'])->toEqual('100');
    expect($panel->prices()[1]['isMissing'])->toBeTrue();
    expect($panel->categoryOptions())->toEqual([['id' => $colours->id, 'label' => 'Hair Coloring']]);
    expect($panel->steps())->toHaveCount(2);
    expect($panel->steps()[1]['requires_staff'])->toBeFalse();
    expect($panel->processingName()['en'])->toEqual('Processing');
    expect($panel->translationStatus()['en'])->toEqual('1 to translate in English');
    expect($panel->translationStatus()['fr'])->toEqual('3 to translate in Français');
});
