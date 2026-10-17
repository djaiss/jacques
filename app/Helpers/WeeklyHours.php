<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * A week of time ranges, keyed by day of the week from 0 for Sunday to 6 for
 * Saturday, each day a list of ranges written HH:MM. A day with no range is
 * a day off. Opening hours and working hours share this shape.
 */
class WeeklyHours
{
    /**
     * The times a range can start or end at on the onboarding screens: every
     * half hour from 06:00 to 22:00.
     *
     * @return list<string>
     */
    public static function grid(): array
    {
        $times = [];

        for ($minutes = 6 * 60; $minutes <= 22 * 60; $minutes += 30) {
            $times[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }

        return $times;
    }

    /**
     * @param  array<int, list<array{start: string, end: string}>>  $week
     *
     * @throws InvalidArgumentException
     */
    public static function validate(array $week): void
    {
        foreach ($week as $day => $ranges) {
            if ($day < 0 || $day > 6) {
                throw new InvalidArgumentException('The day of the week has to be between 0 for Sunday and 6 for Saturday');
            }

            foreach ($ranges as $range) {
                if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $range['start']) !== 1) {
                    throw new InvalidArgumentException('A range starts at a time written as HH:MM');
                }

                if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $range['end']) !== 1) {
                    throw new InvalidArgumentException('A range ends at a time written as HH:MM');
                }

                if ($range['end'] <= $range['start']) {
                    throw new InvalidArgumentException('A range has to end after it starts');
                }
            }

            $sorted = collect($ranges)->sortBy('start')->values();

            for ($index = 1; $index < $sorted->count(); $index++) {
                if ($sorted[$index]['start'] < $sorted[$index - 1]['end']) {
                    throw new InvalidArgumentException('Two ranges of the same day cannot overlap');
                }
            }
        }
    }

    /**
     * Build the week from stored rows, each with a day of the week and times
     * written H:i:s.
     *
     * @param  Collection<int, covariant object{day_of_week: int, start_time: string, end_time: string}>  $rows
     * @return array<int, list<array{start: string, end: string}>>
     */
    public static function fromRows(Collection $rows): array
    {
        $week = array_fill(0, 7, []);

        foreach ($rows->sortBy('start_time') as $row) {
            $week[$row->day_of_week][] = [
                'start' => mb_substr($row->start_time, 0, 5),
                'end' => mb_substr($row->end_time, 0, 5),
            ];
        }

        return $week;
    }

    /**
     * Build the week from the days a form submits, each with whether it is
     * open and its ranges. The ranges of a closed day are left out.
     *
     * @param  array<int|string, array{open?: mixed, ranges?: array<int, array{start: string, end: string}>}>  $days
     * @return array<int, list<array{start: string, end: string}>>
     */
    public static function fromForm(array $days): array
    {
        $week = array_fill(0, 7, []);

        foreach ($days as $day => $values) {
            if (! filter_var($values['open'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            foreach ($values['ranges'] ?? [] as $range) {
                $week[(int) $day][] = [
                    'start' => $range['start'],
                    'end' => $range['end'],
                ];
            }
        }

        return $week;
    }
}
