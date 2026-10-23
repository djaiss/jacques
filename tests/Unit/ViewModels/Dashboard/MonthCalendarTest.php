<?php

declare(strict_types=1);

use App\ViewModels\Dashboard\MonthCalendar;
use Carbon\CarbonImmutable;

it('names the month it shows', function () {
    $calendar = new MonthCalendar(
        selected: CarbonImmutable::parse('2026-10-26'),
        month: CarbonImmutable::parse('2026-10-01'),
        today: CarbonImmutable::parse('2026-10-23'),
    );

    expect($calendar->label())->toBe('October 2026');
});

it('starts every week on monday', function () {
    $calendar = new MonthCalendar(
        selected: CarbonImmutable::parse('2026-10-26'),
        month: CarbonImmutable::parse('2026-10-01'),
        today: CarbonImmutable::parse('2026-10-23'),
    );

    $weeks = $calendar->weeks();

    expect($calendar->weekdays()[0])->toBe(['initial' => 'M', 'name' => 'Monday']);
    expect($calendar->weekdays()[6])->toBe(['initial' => 'S', 'name' => 'Sunday']);
    expect($weeks)->toHaveCount(5);
    expect($weeks[0][0]['number'])->toBe(28);
    expect($weeks[0][0]['inMonth'])->toBeFalse();
    expect($weeks[4][6]['number'])->toBe(1);
});

it('flags the selected day, today and sundays', function () {
    $calendar = new MonthCalendar(
        selected: CarbonImmutable::parse('2026-10-26'),
        month: CarbonImmutable::parse('2026-10-01'),
        today: CarbonImmutable::parse('2026-10-23'),
    );

    $days = collect($calendar->weeks())->flatten(1);

    expect($days->where('isSelected', true)->pluck('number')->all())->toBe([26]);
    expect($days->where('isToday', true)->pluck('number')->all())->toBe([23]);
    expect($days->firstWhere('number', 4)['isSunday'])->toBeTrue();
    expect($days->firstWhere('number', 26)['url'])->toBe(route('dashboard.show', ['date' => '2026-10-26']));
    expect($days->firstWhere('number', 26)['label'])->toBe('Monday 26 October 2026');
});

it('pages through months without changing the selected day', function () {
    $calendar = new MonthCalendar(
        selected: CarbonImmutable::parse('2026-10-26'),
        month: CarbonImmutable::parse('2026-10-01'),
        today: CarbonImmutable::parse('2026-10-23'),
    );

    expect($calendar->previousUrl())->toBe(route('dashboard.calendar.show', ['date' => '2026-10-26', 'month' => '2026-09']));
    expect($calendar->nextUrl())->toBe(route('dashboard.calendar.show', ['date' => '2026-10-26', 'month' => '2026-11']));
});
