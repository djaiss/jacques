<?php

declare(strict_types=1);

use App\Actions\RemoveStaffLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('stops a staff member performing a service at a location', function () {
    Queue::fake();

    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
    $jim->locationServices()->attach($locationService->id);

    new RemoveStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();

    $this->assertDatabaseMissing('staff_location_services', ['staff_id' => $jim->id, 'location_service_id' => $locationService->id]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffLocationServiceRemoved,
    );
});

it('throws when the staff member belongs to another company', function () {
    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

    new RemoveStaffLocationService(author: $michael, staff: Staff::factory()->create(), locationService: $locationService)->execute();
})->throws(ModelNotFoundException::class);

it('throws when a manager changes another location', function () {
    $atStamford = LocationService::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $scranton->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);

    new RemoveStaffLocationService(author: $dwight, staff: $jim, locationService: $atStamford)->execute();
})->throws(ModelNotFoundException::class);
