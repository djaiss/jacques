<?php

declare(strict_types=1);

use App\Actions\AssignStaffLocationService;
use App\Actions\DeactivateLocationStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('pauses an assignment and keeps what was set up there', function () {
    Queue::fake();

    $scranton = Location::factory()->create(['name' => 'Scranton']);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
    $jim->locations()->attach($scranton);
    $workingHour = StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::RoleManage);

    $staff = new DeactivateLocationStaff(author: $michael, staff: $jim, location: $scranton)->execute();

    expect($staff)->toBeInstanceOf(Staff::class);
    $this->assertDatabaseHas('location_staff', [
        'staff_id' => $jim->id,
        'location_id' => $scranton->id,
        'is_active' => false,
    ]);
    expect($jim->activeLocations()->whereKey($scranton->id)->exists())->toBeFalse();
    $this->assertModelExists($workingHour);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationStaffDeactivated
            && $job->parameters === ['name' => 'Scranton', 'staff' => 'Jim Halpert'],
    );
});

it('refuses new services at a paused location', function () {
    $scranton = Location::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach($scranton);
    $haircut = LocationService::factory()->create(['location_id' => $scranton->id]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::OWNER);

    new DeactivateLocationStaff(author: $michael, staff: $jim, location: $scranton)->execute();

    new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $haircut)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member is not assigned to the location', function () {
    $stamford = Location::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $stamford->company_id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $stamford->company_id]), PermissionEnum::RoleManage);

    new DeactivateLocationStaff(author: $michael, staff: $jim, location: $stamford)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member belongs to another company', function () {
    $scranton = Location::factory()->create();
    $karen = Staff::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::RoleManage);

    new DeactivateLocationStaff(author: $michael, staff: $karen, location: $scranton)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author may not decide who works where', function () {
    $scranton = Location::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach($scranton);
    $toby = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::StaffView);

    new DeactivateLocationStaff(author: $toby, staff: $jim, location: $scranton)->execute();
})->throws(ModelNotFoundException::class);
