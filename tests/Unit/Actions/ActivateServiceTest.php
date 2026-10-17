<?php

declare(strict_types=1);

use App\Actions\ActivateService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('activates a service', function () {
    Queue::fake();

    $service = Service::factory()->inactive()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new ActivateService(author: $michael, service: $service)->execute();

    expect($service->refresh()->is_active)->toBeTrue();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceActivated,
    );
});

it('throws when a manager changes the catalogue', function () {
    $service = Service::factory()->inactive()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

    new ActivateService(author: $dwight, service: $service)->execute();
})->throws(ModelNotFoundException::class);
