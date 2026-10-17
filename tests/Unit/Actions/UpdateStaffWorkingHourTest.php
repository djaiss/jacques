<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\UpdateStaffWorkingHourTest;

use App\Actions\UpdateStaffWorkingHour;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

it('moves a range to another day and other times', function () {
    Queue::fake();

    $morning = angelaMondayMorning();
    $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

    $workingHour = new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 2, startTime: '08:30', endTime: '11:30')->execute();

    expect($workingHour)->toBeInstanceOf(StaffWorkingHour::class);
    $this->assertDatabaseHas('staff_working_hours', [
        'id' => $morning->id,
        'day_of_week' => 2,
        'start_time' => '08:30:00',
        'end_time' => '11:30:00',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffWorkingHourUpdated,
    );
});

it('lets a range overlap its own previous times', function () {
    $morning = angelaMondayMorning();
    $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

    new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 1, startTime: '10:00', endTime: '13:00')->execute();

    expect($morning->refresh()->start_time)->toBe('10:00:00');
});

it('throws when the new times overlap another range', function () {
    $morning = angelaMondayMorning();
    StaffWorkingHour::factory()->create(['staff_id' => $morning->staff_id, 'location_id' => $morning->location_id, 'day_of_week' => 1, 'start_time' => '13:00:00', 'end_time' => '17:00:00']);
    $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

    new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 1, startTime: '09:00', endTime: '14:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the range ends before it starts', function () {
    $morning = angelaMondayMorning();
    $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

    new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 1, startTime: '12:00', endTime: '09:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the day of the week is out of range', function () {
    $morning = angelaMondayMorning();
    $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

    new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: -1, startTime: '09:00', endTime: '12:00')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the range belongs to another company', function () {
    $morning = angelaMondayMorning();
    $jan = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);

    new UpdateStaffWorkingHour(author: $jan, workingHour: $morning, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
})->throws(ModelNotFoundException::class);

function angelaMondayMorning(): StaffWorkingHour
{
    $scranton = Location::factory()->create();
    $angela = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Angela', 'last_name' => 'Martin']);
    $angela->locations()->attach($scranton);

    return StaffWorkingHour::factory()->create([
        'staff_id' => $angela->id,
        'location_id' => $scranton->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);
}
