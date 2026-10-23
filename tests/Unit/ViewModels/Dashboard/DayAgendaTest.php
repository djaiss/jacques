<?php

declare(strict_types=1);

use App\ViewModels\Dashboard\DayAgenda;
use App\ViewModels\Dashboard\GeneratedAgenda;
use Carbon\CarbonImmutable;

it('labels every hour from opening to closing', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-26'), agenda: new GeneratedAgenda);

    $hours = $day->hours();

    expect($hours)->toHaveCount(11);
    expect($hours[0])->toBe(['label' => '9:00 AM', 'top' => 0]);
    expect($hours[10])->toBe(['label' => '7:00 PM', 'top' => 900]);
    expect($day->height())->toBe(900);
});

it('hatches the time before arrival and the lunch break', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-26'), agenda: new GeneratedAgenda);

    $amelie = $day->columns()[0];

    expect($amelie['name'])->toBe('Amélie');
    expect($amelie['offBlocks'])->toBe([
        ['top' => 0, 'height' => 90, 'label' => 'Not arrived yet'],
        ['top' => 315, 'height' => 90, 'label' => 'Lunch break'],
    ]);
    expect($amelie['hoursLabel'])->toBe('10:00 AM to 7:00 PM');
    expect($amelie['lunchLabel'])->toBe('Lunch 12:30 PM');
});

it('hatches the time after somebody leaves', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-26'), agenda: new GeneratedAgenda);

    $charlotte = $day->columns()[2];

    expect($charlotte['offBlocks'][1])->toBe(['top' => 810, 'height' => 90, 'label' => 'Gone']);
});

it('hatches the whole day of somebody on leave', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-27'), agenda: new GeneratedAgenda);

    $charlotte = $day->columns()[2];

    expect($charlotte['absent'])->toBeTrue();
    expect($charlotte['countLabel'])->toBe('Absent all day');
    expect($charlotte['offBlocks'])->toBe([['top' => 0, 'height' => 900, 'label' => 'On leave']]);
    expect($charlotte['workingRanges'])->toBe([]);
});

it('places a short appointment on one line', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-26'), agenda: new GeneratedAgenda);

    $appointment = $day->columns()[0]['appointments'][0];

    expect($appointment['top'])->toBe(181);
    expect($appointment['height'])->toBe(28);
    expect($appointment['compact'])->toBeTrue();
    expect($appointment['time'])->toBe('11:00 AM to 11:20 AM');
    expect($appointment['hairdresserName'])->toBe('Amélie');
});

it('places a long appointment on three lines', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-26'), agenda: new GeneratedAgenda);

    $appointment = $day->columns()[2]['appointments'][0];

    expect($appointment['height'])->toBe(178);
    expect($appointment['compact'])->toBeFalse();
});

it('counts the appointments of each hairdresser', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-26'), agenda: new GeneratedAgenda);

    expect($day->columns()[0]['countLabel'])->toBe('7 appointments');
});

it('has no column on a sunday', function () {
    $day = new DayAgenda(date: CarbonImmutable::parse('2026-10-25'), agenda: new GeneratedAgenda);

    expect($day->columns())->toBe([]);
});
