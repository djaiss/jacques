<?php

declare(strict_types=1);

use App\Actions\UpdateServicePosition;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('moves a service within its category only', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 1]);
    $fringe = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 2]);
    $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);

    new UpdateServicePosition(author: $michael, service: $fringe, position: 1)->execute();

    expect($cuts->services()->pluck('id')->all())->toEqual([$fringe->id, $haircut->id]);
    expect($shave->refresh()->position)->toEqual(1);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceMoved,
    );
});

it('moves a service without a category among the others without one', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $trim = Service::factory()->create(['company_id' => $company->id, 'position' => 2]);

    new UpdateServicePosition(author: $michael, service: $shave, position: 2)->execute();

    expect($shave->refresh()->position)->toEqual(2);
    expect($trim->refresh()->position)->toEqual(1);
});

it('throws when the position is out of range', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateServicePosition(author: $michael, service: $service, position: 0)->execute();
})->throws(InvalidArgumentException::class);

it('throws when a manager changes the catalogue', function () {
    $service = Service::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

    new UpdateServicePosition(author: $dwight, service: $service, position: 1)->execute();
})->throws(ModelNotFoundException::class);
