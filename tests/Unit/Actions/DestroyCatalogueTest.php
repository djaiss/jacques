<?php

declare(strict_types=1);

use App\Actions\DestroyCatalogue;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('removes every category and service of the company only', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
    Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id]);
    Service::factory()->create(['company_id' => $company->id]);
    $elsewhere = Service::factory()->create();

    new DestroyCatalogue(author: $michael, company: $company)->execute();

    expect(Service::query()->where('company_id', $company->id)->count())->toEqual(0);
    $this->assertModelMissing($cuts);
    $this->assertModelExists($elsewhere);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CatalogueDestroyed,
    );
});

it('throws when the author belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new DestroyCatalogue(author: $michael, company: Company::factory()->create())->execute();
})->throws(ModelNotFoundException::class);
