<?php

declare(strict_types=1);

use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Staff;

it('belongs to a staff member', function () {
    $staff = Staff::factory()->create();
    $assignment = LocationStaff::factory()->create(['staff_id' => $staff->id]);

    expect($assignment->staff()->exists())->toBeTrue();
    expect($assignment->staff->id)->toEqual($staff->id);
});

it('belongs to a location', function () {
    $location = Location::factory()->create();
    $assignment = LocationStaff::factory()->create(['location_id' => $location->id]);

    expect($assignment->location()->exists())->toBeTrue();
    expect($assignment->location->id)->toEqual($location->id);
});

it('says when the staff member was assigned', function () {
    $assignment = LocationStaff::factory()->create();

    expect($assignment->created_at)->not->toBeNull();
    expect($assignment->created_at->timestamp)->toEqualWithDelta(now()->timestamp, 2);
});

it('casts is active to a boolean', function () {
    $active = LocationStaff::factory()->create()->refresh();
    $paused = LocationStaff::factory()->paused()->create()->refresh();

    expect($active->is_active)->toBeTrue();
    expect($paused->is_active)->toBeFalse();
});
