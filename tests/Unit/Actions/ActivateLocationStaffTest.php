<?php

declare(strict_types=1);

use App\Actions\ActivateLocationStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('resumes a paused assignment', function () {
    Queue::fake();

    $stamford = Location::factory()->create();
    $andy = Staff::factory()->create(['company_id' => $stamford->company_id]);
    $assignment = LocationStaff::factory()->paused()->create(['location_id' => $stamford->id, 'staff_id' => $andy->id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $stamford->company_id]), PermissionEnum::RoleManage);

    $staff = new ActivateLocationStaff(author: $michael, staff: $andy, location: $stamford)->execute();

    expect($staff)->toBeInstanceOf(Staff::class);
    expect($assignment->refresh()->is_active)->toBeTrue();
    expect($staff->activeLocations()->whereKey($stamford->id)->exists())->toBeTrue();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationStaffActivated,
    );
});

it('throws when the staff member is not assigned to the location', function () {
    $stamford = Location::factory()->create();
    $andy = Staff::factory()->create(['company_id' => $stamford->company_id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $stamford->company_id]), PermissionEnum::RoleManage);

    new ActivateLocationStaff(author: $michael, staff: $andy, location: $stamford)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the location belongs to another company', function () {
    $andy = Staff::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $andy->company_id]), PermissionEnum::RoleManage);

    new ActivateLocationStaff(author: $michael, staff: $andy, location: Location::factory()->create())->execute();
})->throws(ModelNotFoundException::class);
