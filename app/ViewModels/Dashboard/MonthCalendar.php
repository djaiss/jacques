<?php

declare(strict_types=1);

namespace App\ViewModels\Dashboard;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class MonthCalendar
{
    public function __construct(
        private readonly CarbonImmutable $selected,
        private readonly CarbonImmutable $month,
        private readonly CarbonImmutable $today,
    ) {}

    public function label(): string
    {
        return $this->month->isoFormat('MMMM YYYY');
    }

    /**
     * @return list<array{initial: string, name: string}>
     */
    public function weekdays(): array
    {
        $monday = $this->month->startOfWeek(CarbonInterface::MONDAY);

        return array_map(fn (int $offset): array => [
            'initial' => mb_strtoupper(mb_substr($monday->addDays($offset)->isoFormat('dd'), 0, 1)),
            'name' => $monday->addDays($offset)->isoFormat('dddd'),
        ], range(0, 6));
    }

    /**
     * @return list<list<array{number: int, label: string, url: string, inMonth: bool, isSelected: bool, isToday: bool, isSunday: bool}>>
     */
    public function weeks(): array
    {
        $weeks = [];
        $day = $this->month->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
        $last = $this->month->endOfMonth();

        while ($day->lte($last)) {
            $week = [];

            for ($index = 0; $index < 7; $index++) {
                $week[] = [
                    'number' => $day->day,
                    'label' => $day->isoFormat('dddd D MMMM YYYY'),
                    'url' => route('dashboard.show', ['date' => $day->format('Y-m-d')]),
                    'inMonth' => $day->isSameMonth($this->month),
                    'isSelected' => $day->isSameDay($this->selected),
                    'isToday' => $day->isSameDay($this->today),
                    'isSunday' => $day->isSunday(),
                ];

                $day = $day->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    public function previousUrl(): string
    {
        return route('dashboard.calendar.show', [
            'date' => $this->selected->format('Y-m-d'),
            'month' => $this->month->subMonthNoOverflow()->format('Y-m'),
        ]);
    }

    public function nextUrl(): string
    {
        return route('dashboard.calendar.show', [
            'date' => $this->selected->format('Y-m-d'),
            'month' => $this->month->addMonthNoOverflow()->format('Y-m'),
        ]);
    }
}
