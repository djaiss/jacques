<?php

declare(strict_types=1);

use App\Actions\SyncStaffWorkingHours;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('replaces the week a staff member works at a location', function () {
    Queue::fake();

    $scranton = Location::factory()->create(['name' => 'Scranton']);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
    $jim->locations()->attach($scranton->id);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 1]);

    $result = new SyncStaffWorkingHours(
        author: $michael,
        staff: $jim,
        location: $scranton,
        week: [2 => [['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '18:00']]],
        followsOpeningHours: false,
    )->execute();

    expect($result)->toBeInstanceOf(Staff::class);
    expect($jim->workingHours()->pluck('day_of_week')->all())->toEqual([2, 2]);
    expect((bool) $jim->locations()->first()->pivot->follows_opening_hours)->toBeFalse();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffWorkingHoursUpdated
            && $job->parameters === ['name' => 'Jim Halpert', 'location' => 'Scranton'],
    );
});

it('keeps the hours worked at another location', function () {
    Queue::fake();

    $scranton = Location::factory()->create();
    $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach([$scranton->id, $stamford->id]);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $stamford->id]);

    new SyncStaffWorkingHours(author: $michael, staff: $jim, location: $scranton, week: [], followsOpeningHours: true)->execute();

    expect($jim->workingHours()->pluck('location_id')->all())->toEqual([$stamford->id]);
    expect((bool) $jim->locations()->whereKey($scranton->id)->first()->pivot->follows_opening_hours)->toBeTrue();
});

it('throws when the staff member is not assigned to the location', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
    $toby = Staff::factory()->create(['company_id' => $scranton->company_id]);

    new SyncStaffWorkingHours(author: $michael, staff: $toby, location: $scranton, week: [], followsOpeningHours: true)->execute();
})->throws(InvalidArgumentException::class);

it('throws when two ranges of a day overlap', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach($scranton->id);

    new SyncStaffWorkingHours(
        author: $michael,
        staff: $jim,
        location: $scranton,
        week: [3 => [['start' => '09:00', 'end' => '14:00'], ['start' => '12:00', 'end' => '18:00']]],
        followsOpeningHours: false,
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the location belongs to another company', function () {
    $nashua = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id]);

    new SyncStaffWorkingHours(author: $michael, staff: $jim, location: $nashua, week: [], followsOpeningHours: true)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach($scranton->id);

    new SyncStaffWorkingHours(author: $michael, staff: $jim, location: $scranton, week: [], followsOpeningHours: true)->execute();
})->throws(ModelNotFoundException::class);
