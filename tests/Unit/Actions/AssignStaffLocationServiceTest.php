<?php

declare(strict_types=1);

use App\Actions\AssignStaffLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\LocationStaff;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('lets a staff member perform a service at their location', function () {
    Queue::fake();

    $scranton = Location::factory()->create(['name' => 'Scranton']);
    $haircut = Service::factory()->create(['company_id' => $scranton->company_id, 'name' => ['en' => 'Haircut']]);
    $locationService = LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
    $jim->locations()->attach($scranton->id);

    $result = new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();

    expect($result)->toBeInstanceOf(Staff::class);
    $this->assertDatabaseHas('staff_location_services', ['staff_id' => $jim->id, 'location_service_id' => $locationService->id]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffLocationServiceAssigned
            && $job->parameters === [
                'name' => 'Haircut',
                'location' => 'Scranton',
                'staff' => 'Jim Halpert',
            ],
    );
});

it('changes nothing when the staff member already performs it', function () {
    Queue::fake();

    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
    $jim->locations()->attach($locationService->location_id);
    $jim->locationServices()->attach($locationService->id);

    new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();

    expect($jim->locationServices()->get())->toHaveCount(1);
});

it('throws when the staff member is not assigned to the location', function () {
    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);

    new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the assignment of the staff member to the location is paused', function () {
    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
    LocationStaff::factory()->paused()->create(['location_id' => $locationService->location_id, 'staff_id' => $jim->id]);

    new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
})->throws(InvalidArgumentException::class, 'A staff member can only perform a service at a location where their assignment is active');

it('throws when the location does not offer the service at the moment', function () {
    $locationService = LocationService::factory()->create(['is_active' => false]);
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
    $jim->locations()->attach($locationService->location_id);

    new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the service is deactivated', function () {
    $locationService = LocationService::factory()->create();
    $locationService->service->update(['is_active' => false]);
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
    $jim->locations()->attach($locationService->location_id);

    new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member belongs to another company', function () {
    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

    new AssignStaffLocationService(author: $michael, staff: Staff::factory()->create(), locationService: $locationService)->execute();
})->throws(ModelNotFoundException::class);

it('throws when a manager changes another location', function () {
    $atStamford = LocationService::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $scranton->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach($atStamford->location_id);

    new AssignStaffLocationService(author: $dwight, staff: $jim, locationService: $atStamford)->execute();
})->throws(ModelNotFoundException::class);
