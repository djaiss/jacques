<?php

declare(strict_types=1);

use App\Actions\DestroyService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\StaffLocationService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('deletes a service with its steps offers and staff assignments', function () {
    Queue::fake();

    $locationService = LocationService::factory()->create();
    $service = $locationService->service;
    $assignment = StaffLocationService::factory()->create(['location_service_id' => $locationService->id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new DestroyService(author: $michael, service: $service)->execute();

    $this->assertModelMissing($service);
    $this->assertModelMissing($locationService);
    $this->assertModelMissing($assignment);
    $this->assertDatabaseMissing('service_steps', ['service_id' => $service->id]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceDeleted
            && $job->parameters === ['name' => $service->name['en']],
    );
});

it('moves up the services after it', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $trim = Service::factory()->create(['company_id' => $company->id, 'position' => 2]);

    new DestroyService(author: $michael, service: $shave)->execute();

    expect($trim->refresh()->position)->toEqual(1);
});

it('throws when a manager changes the catalogue', function () {
    $service = Service::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

    new DestroyService(author: $dwight, service: $service)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the service belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new DestroyService(author: $michael, service: Service::factory()->create())->execute();
})->throws(ModelNotFoundException::class);
