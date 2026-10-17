<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\CreateStaffWorkingHourTest;

use App\Actions\CreateStaffWorkingHour;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

it('adds two ranges on the same day leaving a break', function () {
    Queue::fake();

    [$michael, $dwight, $scranton] = dwightAtScranton();

    $morning = new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
    $afternoon = new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '13:00', endTime: '18:00')->execute();

    expect($morning)->toBeInstanceOf(StaffWorkingHour::class);
    $this->assertDatabaseHas('staff_working_hours', [
        'id' => $morning->id,
        'staff_id' => $dwight->id,
        'location_id' => $scranton->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);
    $this->assertDatabaseHas('staff_working_hours', [
        'id' => $afternoon->id,
        'start_time' => '13:00:00',
        'end_time' => '18:00:00',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffWorkingHourCreated,
    );
});

it('accepts a range that starts when another ends', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();
    StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $scranton->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '12:00', endTime: '14:00')->execute();

    expect($dwight->workingHours()->count())->toBe(2);
});

it('accepts the same range on another day', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();
    StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $scranton->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 2, startTime: '09:00', endTime: '12:00')->execute();

    expect($dwight->workingHours()->count())->toBe(2);
});

it('throws when the range overlaps another on the same day', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();
    StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $scranton->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '11:00', endTime: '14:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the range ends before it starts', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '18:00', endTime: '09:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the range starts and ends at the same time', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '09:00', endTime: '09:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the day of the week is out of range', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 7, startTime: '09:00', endTime: '12:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when a time is malformed', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '25:00', endTime: '26:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member is not assigned to the location', function () {
    [$michael, $dwight, $scranton] = dwightAtScranton();
    $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $stamford, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the location belongs to another company', function () {
    [$michael, $dwight] = dwightAtScranton();

    new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: Location::factory()->create(), dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    [$michael] = dwightAtScranton();
    $karen = Staff::factory()->create();
    $nashua = Location::factory()->create(['company_id' => $karen->company_id]);
    $karen->locations()->attach($nashua);

    new CreateStaffWorkingHour(author: $michael, staff: $karen, location: $nashua, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
})->throws(ModelNotFoundException::class);

it('throws when a staff member sets the hours of a colleague', function () {
    [, $dwight, $scranton] = dwightAtScranton();
    $jim = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::STAFF);
    Staff::factory()->create(['company_id' => $scranton->company_id, 'user_id' => $jim->id]);

    new CreateStaffWorkingHour(author: $jim, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
})->throws(ModelNotFoundException::class);

/** @return array{0: User, 1: Staff, 2: Location} */
function dwightAtScranton(): array
{
    $scranton = Location::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
    $dwight->locations()->attach($scranton);
    $michael = test()->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage, ScopeEnum::Company);

    return [$michael, $dwight, $scranton];
}
