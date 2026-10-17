<?php

declare(strict_types=1);

use App\Actions\UpdateServiceDuration;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('changes the duration of a one step service', function () {
    Queue::fake();

    $service = Service::factory()->create(['name' => ['en' => 'Beard trim']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    $result = new UpdateServiceDuration(author: $michael, service: $service, duration: 20)->execute();

    expect($result)->toBeInstanceOf(Service::class);
    expect($service->refresh()->duration)->toEqual(20);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceDurationUpdated
            && $job->parameters === ['name' => 'Beard trim'],
    );
});

it('throws for a service made of several steps', function () {
    $service = Service::factory()->create();
    ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2]);
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateServiceDuration(author: $michael, service: $service, duration: 20)->execute();
})->throws(InvalidArgumentException::class);

it('throws for a duration below one minute', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateServiceDuration(author: $michael, service: $service, duration: 0)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author belongs to another company', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new UpdateServiceDuration(author: $michael, service: $service, duration: 20)->execute();
})->throws(ModelNotFoundException::class);
