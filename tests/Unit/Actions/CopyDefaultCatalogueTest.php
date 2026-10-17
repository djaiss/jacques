<?php

declare(strict_types=1);

use App\Actions\CopyDefaultCatalogue;
use App\Models\Company;
use App\Models\ServiceCategory;

it('writes the example after the categories already there', function () {
    $company = Company::factory()->create();
    ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);

    new CopyDefaultCatalogue(company: $company)->execute();

    expect(ServiceCategory::query()->where('company_id', $company->id)->orderBy('position')->pluck('position')->all())->toEqual(range(1, 7));
});
