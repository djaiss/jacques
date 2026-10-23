<?php

declare(strict_types=1);

use App\ViewModels\Dashboard\DashboardViewModel;
use Carbon\CarbonImmutable;

it('titles the day it shows', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-26'),
        now: CarbonImmutable::parse('2026-10-23 10:30'),
    );

    expect($viewModel->title())->toBe('Monday 26 October 2026');
    expect($viewModel->isToday())->toBeFalse();
    expect($viewModel->summary())->toBe('32 appointments in total');
});

it('says when the day shown is today', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-26'),
        now: CarbonImmutable::parse('2026-10-26 10:30'),
    );

    expect($viewModel->isToday())->toBeTrue();
    expect($viewModel->summary())->toBe('Today, 32 appointments in total');
});

it('closes the salon on a sunday', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-25'),
        now: CarbonImmutable::parse('2026-10-25 10:30'),
    );

    expect($viewModel->isClosed())->toBeTrue();
    expect($viewModel->summary())->toBe('Salon closed');
    expect($viewModel->showsCurrentTime())->toBeFalse();
});

it('draws the current time on today during opening hours', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-26'),
        now: CarbonImmutable::parse('2026-10-26 10:30'),
    );

    expect($viewModel->showsCurrentTime())->toBeTrue();
    expect($viewModel->currentTimeTop())->toBe(135);
    expect($viewModel->currentTimeLabel())->toBe('10:30 AM');
});

it('hides the current time after closing', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-26'),
        now: CarbonImmutable::parse('2026-10-26 20:00'),
    );

    expect($viewModel->showsCurrentTime())->toBeFalse();
});

it('hides the current time on another day', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-27'),
        now: CarbonImmutable::parse('2026-10-26 10:30'),
    );

    expect($viewModel->showsCurrentTime())->toBeFalse();
});

it('links to the days around the one shown', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-26'),
        now: CarbonImmutable::parse('2026-10-23 10:30'),
    );

    expect($viewModel->previousDayUrl())->toBe(route('dashboard.show', ['date' => '2026-10-25']));
    expect($viewModel->nextDayUrl())->toBe(route('dashboard.show', ['date' => '2026-10-27']));
    expect($viewModel->todayUrl())->toBe(route('dashboard.index'));
});

it('opens the calendar on the month asked for', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-26'),
        now: CarbonImmutable::parse('2026-10-23 10:30'),
        month: CarbonImmutable::parse('2026-12-01'),
    );

    expect($viewModel->calendar()->label())->toBe('December 2026');
});

it('hands the browser what it needs to redraw the day', function () {
    $viewModel = new DashboardViewModel(
        date: CarbonImmutable::parse('2026-10-26'),
        now: CarbonImmutable::parse('2026-10-23 10:30'),
    );

    $state = $viewModel->alpineState();

    expect($state['visible'])->toBe(['amelie', 'sammy', 'charlotte', 'lucas']);
    expect($state['slot'])->toBe(20);
    expect($state['countLabels'][1])->toBe('1 appointment');
    expect($state['countLabels'][7])->toBe('7 appointments');
    expect($state['summaryLabels'][32])->toBe('32 appointments in total');
    expect($state['columns']['amelie']['workingRanges'])->toBe([[600, 750], [810, 1140]]);
    expect($state['columns']['amelie']['appointments'])->toHaveCount(7);
});
