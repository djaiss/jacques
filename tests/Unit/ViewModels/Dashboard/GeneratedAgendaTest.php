<?php

declare(strict_types=1);

use App\ViewModels\Dashboard\GeneratedAgenda;
use Carbon\CarbonImmutable;

it('gives the same day every time it is asked', function () {
    $agenda = new GeneratedAgenda;
    $monday = CarbonImmutable::parse('2026-10-26');

    expect($agenda->plan('amelie', $monday))->toBe($agenda->plan('amelie', $monday));
});

it('gives different days to different hairdressers', function () {
    $agenda = new GeneratedAgenda;
    $monday = CarbonImmutable::parse('2026-10-26');

    expect($agenda->plan('amelie', $monday))->not->toBe($agenda->plan('sammy', $monday));
});

it('closes the salon on a sunday', function () {
    $agenda = new GeneratedAgenda;

    expect($agenda->plan('amelie', CarbonImmutable::parse('2026-10-25')))->toBeNull();
});

it('keeps every appointment inside the working hours and out of lunch', function () {
    $agenda = new GeneratedAgenda;

    foreach (CarbonImmutable::parse('2026-10-26')->toPeriod('2026-11-21') as $date) {
        $plan = $agenda->plan('charlotte', CarbonImmutable::instance($date));

        if ($plan === null) {
            continue;
        }

        foreach ($plan['appointments'] as $appointment) {
            $end = $appointment['start'] + $appointment['duration'];

            expect($appointment['start'])->toBeGreaterThanOrEqual($plan['start']);
            expect($end)->toBeLessThanOrEqual($plan['end']);
            expect($end <= $plan['lunchStart'] || $appointment['start'] >= $plan['lunchStart'] + GeneratedAgenda::LUNCH_LENGTH)->toBeTrue();
        }
    }
});

it('gives no appointment to a hairdresser on leave', function () {
    $agenda = new GeneratedAgenda;

    $plans = collect(CarbonImmutable::parse('2026-01-05')->toPeriod('2026-06-30'))
        ->map(fn ($date) => $agenda->plan('lucas', CarbonImmutable::instance($date)))
        ->filter(fn (?array $plan): bool => $plan !== null && $plan['absent']);

    expect($plans)->not->toBeEmpty();
    $plans->each(fn (array $plan) => expect($plan['appointments'])->toBe([]));
});

it('lists five hairdressers with alexandra hidden at first', function () {
    $hairdressers = collect((new GeneratedAgenda)->hairdressers());

    expect($hairdressers)->toHaveCount(5);
    expect($hairdressers->where('visible', false)->pluck('id')->all())->toBe(['alexandra']);
});
