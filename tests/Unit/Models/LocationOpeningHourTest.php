<?php

declare(strict_types=1);

use App\Models\LocationOpeningHour;

it('belongs to a location', function () {
    $openingHour = LocationOpeningHour::factory()->create();

    expect($openingHour->location()->exists())->toBeTrue();
});

it('casts the day of the week to an integer', function () {
    $openingHour = LocationOpeningHour::factory()->create(['day_of_week' => '6']);

    expect($openingHour->refresh()->day_of_week)->toBe(6);
});
