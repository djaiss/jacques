<?php

declare(strict_types=1);

use App\Actions\DestroyLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffLocationService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('stops offering a service and forgets who performed it there', function () {
    Queue::fake();

    $locationService = LocationService::factory()->create();
    $assignment = StaffLocationService::factory()->create(['location_service_id' => $locationService->id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

    new DestroyLocationService(author: $michael, locationService: $locationService)->execute();

    $this->assertModelMissing($locationService);
    $this->assertModelMissing($assignment);
    $this->assertModelExists($locationService->service);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationServiceDeleted,
    );
});

it('throws when a manager changes another location', function () {
    $atStamford = LocationService::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $dwight->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);

    new DestroyLocationService(author: $dwight, locationService: $atStamford)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the location belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new DestroyLocationService(author: $michael, locationService: LocationService::factory()->create())->execute();
})->throws(ModelNotFoundException::class);
