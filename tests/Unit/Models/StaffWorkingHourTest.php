<?php

declare(strict_types=1);

use App\Models\StaffWorkingHour;

it('belongs to a staff member', function () {
    $workingHour = StaffWorkingHour::factory()->create();

    expect($workingHour->staff()->exists())->toBeTrue();
});

it('belongs to a location', function () {
    $workingHour = StaffWorkingHour::factory()->create();

    expect($workingHour->location()->exists())->toBeTrue();
});

it('casts the day of the week to an integer', function () {
    $workingHour = StaffWorkingHour::factory()->create(['day_of_week' => '3']);

    expect($workingHour->refresh()->day_of_week)->toBe(3);
});
