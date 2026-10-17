<?php

declare(strict_types=1);

use App\Actions\RestoreDefaultCatalogue;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('brings back the example catalogue even after it was created once', function () {
    Queue::fake();

    $company = Company::factory()->create(['default_catalogue_created_at' => now()]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new RestoreDefaultCatalogue(author: $michael, company: $company)->execute();

    expect(ServiceCategory::query()->where('company_id', $company->id)->count())->toEqual(6);
    expect(Service::query()->where('company_id', $company->id)->count())->toEqual(17);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CatalogueRestored,
    );
});

it('throws when the catalogue is not empty', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    ServiceCategory::factory()->create(['company_id' => $company->id]);

    new RestoreDefaultCatalogue(author: $michael, company: $company)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new RestoreDefaultCatalogue(author: $michael, company: Company::factory()->create())->execute();
})->throws(ModelNotFoundException::class);
