<?php

declare(strict_types=1);

use App\Models\Location;
use App\Models\StaffAbsence;
use Carbon\Carbon;

it('belongs to a staff member', function () {
    $absence = StaffAbsence::factory()->create();

    expect($absence->staff()->exists())->toBeTrue();
});

it('belongs to a location when it concerns only one', function () {
    $absence = StaffAbsence::factory()->create(['location_id' => Location::factory()->create()->id]);

    expect($absence->location()->exists())->toBeTrue();
    expect($absence->concernsEveryLocation())->toBeFalse();
});

it('concerns every location without one', function () {
    $absence = StaffAbsence::factory()->create(['location_id' => null]);

    expect($absence->location()->exists())->toBeFalse();
    expect($absence->concernsEveryLocation())->toBeTrue();
});

it('casts its dates', function () {
    $absence = StaffAbsence::factory()->create()->refresh();

    expect($absence->starts_at)->toBeInstanceOf(Carbon::class);
    expect($absence->ends_at)->toBeInstanceOf(Carbon::class);
});
