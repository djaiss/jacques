<?php

declare(strict_types=1);

use App\Actions\DeactivateService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('deactivates a service and keeps where it was offered', function () {
    Queue::fake();

    $locationService = LocationService::factory()->create();
    $service = $locationService->service;
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new DeactivateService(author: $michael, service: $service)->execute();

    expect($service->refresh()->is_active)->toBeFalse();
    $this->assertModelExists($locationService);
    expect($service->steps)->toHaveCount(1);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceDeactivated
            && $job->parameters === ['name' => $service->name['en']],
    );
});

it('throws when a manager changes the catalogue', function () {
    $service = Service::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

    new DeactivateService(author: $dwight, service: $service)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the service belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new DeactivateService(author: $michael, service: Service::factory()->create())->execute();
})->throws(ModelNotFoundException::class);
