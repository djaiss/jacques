<?php

declare(strict_types=1);

use App\Actions\UpdateServicePrices;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('sets and removes prices and keeps those of a currency no longer used', function () {
    Queue::fake();

    $company = Company::factory()->create(['currencies' => ['CAD', 'USD']]);
    $service = Service::factory()->create(['company_id' => $company->id, 'prices' => ['CAD' => 3500, 'USD' => 2600, 'EUR' => 2400]]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new UpdateServicePrices(author: $michael, service: $service, prices: ['CAD' => 4000, 'USD' => null])->execute();

    expect($service->refresh()->prices)->toEqual(['CAD' => 4000, 'EUR' => 2400]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServicePricesUpdated,
    );
});

it('throws for a negative price', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateServicePrices(author: $michael, service: $service, prices: ['CAD' => -1])->execute();
})->throws(InvalidArgumentException::class);

it('throws for a currency the salon does not use', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateServicePrices(author: $michael, service: $service, prices: ['GBP' => 1000])->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author belongs to another company', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new UpdateServicePrices(author: $michael, service: $service, prices: ['CAD' => 1000])->execute();
})->throws(ModelNotFoundException::class);
