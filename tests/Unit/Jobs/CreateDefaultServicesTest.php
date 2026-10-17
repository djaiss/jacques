<?php

declare(strict_types=1);

use App\Jobs\CreateDefaultServices;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('copies the default catalogue into the company', function () {
    $company = Company::factory()->create();

    new CreateDefaultServices(companyId: $company->id)->handle();

    expect(ServiceCategory::query()->where('company_id', $company->id)->count())->toEqual(6);
    expect(Service::query()->where('company_id', $company->id)->count())->toEqual(17);
    expect($company->refresh()->default_catalogue_created_at)->not->toBeNull();
});

it('fails for a company that does not exist', function () {

    new CreateDefaultServices(companyId: 404)->handle();
})->throws(ModelNotFoundException::class);
