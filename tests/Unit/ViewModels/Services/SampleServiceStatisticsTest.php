<?php

declare(strict_types=1);

use App\Models\Service;
use App\ViewModels\Services\SampleServiceStatistics;

it('draws the same figures for a service on every visit', function () {
    $balayage = Service::factory()->create(['prices' => ['CAD' => 18000]]);

    $first = new SampleServiceStatistics(service: $balayage, currencySymbol: 'CA$');
    $second = new SampleServiceStatistics(service: $balayage->fresh(), currencySymbol: 'CA$');

    expect($first->figures([1, 2]))->toEqual($second->figures([1, 2]));
    expect($first->weeks([1, 2]))->toEqual($second->weeks([1, 2]));
    expect($first->insights())->toEqual($second->insights());
});

it('draws other figures for another service', function () {
    $balayage = Service::factory()->create();
    $updo = Service::factory()->create();

    $figures = fn (Service $service): array => array_map(
        fn (int $staffId): int => new SampleServiceStatistics(service: $service, currencySymbol: 'CA$')->bookingsOf($staffId),
        range(1, 10),
    );

    expect($figures($balayage))->not->toEqual($figures($updo));
});

it('counts the bookings of the people who do it', function () {
    $balayage = Service::factory()->create();
    $statistics = new SampleServiceStatistics(service: $balayage, currencySymbol: 'CA$');

    expect($statistics->totalBookings([7, 8]))->toBe($statistics->bookingsOf(7) + $statistics->bookingsOf(8));
    expect($statistics->bookingsOf(7))->toBeGreaterThanOrEqual(2);
    expect($statistics->bookingsOf(7))->toBeLessThanOrEqual(17);
});

it('draws twelve weeks with the last one current', function () {
    $balayage = Service::factory()->create();

    $weeks = new SampleServiceStatistics(service: $balayage, currencySymbol: 'CA$')->weeks([1, 2, 3]);

    expect($weeks)->toHaveCount(12);
    expect($weeks[0]['label'])->toBe('W1');
    expect($weeks[11]['isCurrent'])->toBeTrue();
    expect(max(array_column($weeks, 'height')))->toBe(112);
});

it('draws four figures, the money ones in the currency given', function () {
    $balayage = Service::factory()->create(['prices' => ['CAD' => 18000]]);

    $figures = new SampleServiceStatistics(service: $balayage, currencySymbol: 'CA$')->figures([1]);

    expect(array_column($figures, 'label'))->toBe(['Bookings', 'Earned', 'Earned per booked hour', 'Clients who came back']);
    expect($figures[1]['value'])->toStartWith('CA$ ');
});
