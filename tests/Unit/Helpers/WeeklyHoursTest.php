<?php

declare(strict_types=1);

use App\Helpers\WeeklyHours;
use App\Models\LocationOpeningHour;

it('accepts a day closed for lunch', function () {
    WeeklyHours::validate([6 => [['start' => '13:00', 'end' => '17:00'], ['start' => '09:00', 'end' => '12:00']]]);

    $this->addToAssertionCount(1);
});

it('accepts two ranges that touch', function () {
    WeeklyHours::validate([1 => [['start' => '09:00', 'end' => '12:00'], ['start' => '12:00', 'end' => '18:00']]]);

    $this->addToAssertionCount(1);
});

it('refuses a range that ends when it starts', function () {

    WeeklyHours::validate([1 => [['start' => '09:00', 'end' => '09:00']]]);
})->throws(InvalidArgumentException::class);

it('refuses an hour past midnight', function () {

    WeeklyHours::validate([1 => [['start' => '09:00', 'end' => '24:00']]]);
})->throws(InvalidArgumentException::class);

it('builds the week from stored rows', function () {
    $rows = collect([
        new LocationOpeningHour(['day_of_week' => 6, 'start_time' => '13:00:00', 'end_time' => '17:00:00']),
        new LocationOpeningHour(['day_of_week' => 6, 'start_time' => '09:00:00', 'end_time' => '12:00:00']),
    ]);

    $week = WeeklyHours::fromRows($rows);

    expect($week)->toHaveCount(7);
    expect($week[0])->toEqual([]);
    expect($week[6])->toEqual([['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '17:00']]);
});

it('builds the week from a form and leaves out closed days', function () {
    $week = WeeklyHours::fromForm([
        '0' => ['open' => '0', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
        '1' => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
    ]);

    expect($week[0])->toEqual([]);
    expect($week[1])->toEqual([['start' => '09:00', 'end' => '18:00']]);
    expect($week[6])->toEqual([]);
});
