<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\ApplyOpeningHoursToTeamTest;

use App\Actions\ApplyOpeningHoursToTeam;
use App\Enums\PermissionEnum;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('gives the opening hours to every bookable member who follows them', function () {
    Queue::fake();

    $scranton = openScranton();
    $michael = author($scranton);
    $michaelStaff = member($scranton);
    $jim = member($scranton, customHours: true);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '10:00:00']);
    $pam = member($scranton, attributes: ['is_bookable' => false]);

    $team = new ApplyOpeningHoursToTeam(author: $michael, location: $scranton, overrideCustomHours: false)->execute();

    expect($team->pluck('id')->all())->toEqual([$michaelStaff->id]);
    expect($michaelStaff->workingHours()->orderBy('start_time')->pluck('start_time')->all())->toEqual(['09:00:00', '13:00:00']);
    expect($jim->workingHours()->pluck('start_time')->all())->toEqual(['10:00:00']);
    expect($pam->workingHours()->exists())->toBeFalse();
});

it('overrides custom hours when asked', function () {
    Queue::fake();

    $scranton = openScranton();
    $michael = author($scranton);
    $jim = member($scranton, customHours: true);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 0]);

    new ApplyOpeningHoursToTeam(author: $michael, location: $scranton, overrideCustomHours: true)->execute();

    expect($jim->workingHours()->pluck('day_of_week')->all())->toEqual([6, 6]);
    expect((bool) $jim->locations()->first()->pivot->follows_opening_hours)->toBeTrue();
});

it('leaves out inactive members and paused assignments', function () {
    Queue::fake();

    $scranton = openScranton();
    $michael = author($scranton);
    $toby = member($scranton, attributes: ['is_active' => false]);
    $ryan = Staff::factory()->create(['company_id' => $scranton->company_id]);
    LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $ryan->id]);

    $team = new ApplyOpeningHoursToTeam(author: $michael, location: $scranton, overrideCustomHours: true)->execute();

    expect($team->isEmpty())->toBeTrue();
    expect($toby->workingHours()->exists())->toBeFalse();
    expect($ryan->workingHours()->exists())->toBeFalse();
});

it('throws when the location belongs to another company', function () {
    $nashua = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);

    new ApplyOpeningHoursToTeam(author: $michael, location: $nashua, overrideCustomHours: false)->execute();
})->throws(ModelNotFoundException::class);

function openScranton(): Location
{
    $scranton = Location::factory()->create(['name' => 'Scranton']);
    LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 6, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
    LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 6, 'start_time' => '13:00:00', 'end_time' => '17:00:00']);

    return $scranton;
}

function author(Location $location): User
{
    $user = User::factory()->create(['company_id' => $location->company_id]);
    test()->grant($user, PermissionEnum::LocationManage);

    return test()->grant($user, PermissionEnum::ScheduleManage);
}

/** @param array<string, mixed> $attributes */
function member(Location $location, bool $customHours = false, array $attributes = []): Staff
{
    $staff = Staff::factory()->create(['company_id' => $location->company_id, ...$attributes]);
    $staff->locations()->attach($location->id, ['follows_opening_hours' => ! $customHours]);

    return $staff;
}
