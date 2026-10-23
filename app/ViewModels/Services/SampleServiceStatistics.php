<?php

declare(strict_types=1);

namespace App\ViewModels\Services;

use App\Models\Service;
use Illuminate\Support\Number;

/**
 * Made up figures for the page of a service, drawn the same for a given service
 * on every visit. Nothing books a service yet, so every number here is invented;
 * this class goes away when appointments exist and the page reads real ones.
 */
class SampleServiceStatistics
{
    public function __construct(
        private readonly Service $service,
        private readonly string $currencySymbol,
    ) {}

    public function bookingsOf(int $staffId): int
    {
        return 2 + $this->seed("staff{$staffId}") % 16;
    }

    /** @param  list<int>  $staffIds */
    public function totalBookings(array $staffIds): int
    {
        return array_sum(array_map($this->bookingsOf(...), $staffIds));
    }

    /**
     * @param  list<int>  $staffIds
     * @return list<array{label: string, value: string, trend: string, tone: string, hint: string}>
     */
    public function figures(array $staffIds): array
    {
        $bookings = $this->totalBookings($staffIds);
        $earned = $bookings * $this->price();
        $hours = $bookings * $this->service->duration / 60;
        $perHour = $hours > 0 ? $earned / $hours : 0;
        $locale = app()->getLocale();

        return [
            [
                'label' => __('Bookings'),
                'value' => Number::format($bookings, locale: $locale),
                'trend' => $this->trend($this->bookingTrend()),
                'tone' => $this->tone($this->bookingTrend()),
                'hint' => __('vs previous 30 days'),
            ],
            [
                'label' => __('Earned'),
                'value' => "{$this->currencySymbol} ".Number::format($earned, locale: $locale),
                'trend' => $this->trend($this->earningTrend()),
                'tone' => $this->tone($this->earningTrend()),
                'hint' => __('vs previous 30 days'),
            ],
            [
                'label' => __('Earned per booked hour'),
                'value' => "{$this->currencySymbol} ".Number::format($perHour, precision: 2, locale: $locale),
                'trend' => '',
                'tone' => 'neutral',
                'hint' => trans_choice('Over :count hour booked|Over :count hours booked', (int) round($hours)),
            ],
            [
                'label' => __('Clients who came back'),
                'value' => Number::percentage($this->returningShare(), locale: $locale),
                'trend' => '',
                'tone' => 'neutral',
                'hint' => __('Booked this same service again within 90 days'),
            ],
        ];
    }

    /**
     * @param  list<int>  $staffIds
     * @return list<array{count: int, label: string, height: int, isCurrent: bool}>
     */
    public function weeks(array $staffIds): array
    {
        $perWeek = $this->totalBookings($staffIds) / 4.3;
        $counts = [];

        for ($week = 0; $week < 12; $week++) {
            $counts[] = max(1, (int) round($perWeek * (0.75 + $week * $this->bookingTrend() / 600) + $this->seed("week{$week}") % 5 - 2));
        }

        $highest = max($counts);

        return array_map(fn (int $count, int $week): array => [
            'count' => $count,
            'label' => __('W:number', ['number' => $week + 1]),
            'height' => (int) round($count / $highest * 112),
            'isCurrent' => $week === 11,
        ], $counts, array_keys($counts));
    }

    /** @return list<array{title: string, text: string, tone: string}> */
    public function insights(): array
    {
        $insights = [];
        $trend = $this->bookingTrend();

        if ($trend >= 10) {
            $insights[] = ['title' => __('Growing'), 'text' => __('Booked :percent% more often than in the previous 30 days.', ['percent' => $trend]), 'tone' => 'success'];
        }

        if ($trend <= -10) {
            $insights[] = ['title' => __('Slowing down'), 'text' => __('Booked :percent% less often than in the previous 30 days.', ['percent' => abs($trend)]), 'tone' => 'error'];
        }

        if ($this->returningShare() >= 65) {
            $insights[] = ['title' => __('Clients come back for it'), 'text' => __(':percent% of clients booked it again within 90 days, more than most services in the salon.', ['percent' => $this->returningShare()]), 'tone' => 'success'];
        }

        return $insights;
    }

    private function price(): int
    {
        $prices = $this->service->prices ?? [];

        return intdiv((int) (reset($prices) ?: 0), 100);
    }

    private function bookingTrend(): int
    {
        return $this->seed('bookings') % 41 - 15;
    }

    private function earningTrend(): int
    {
        return $this->bookingTrend() + $this->seed('earnings') % 5 - 2;
    }

    private function returningShare(): int
    {
        return 40 + $this->seed('returning') % 45;
    }

    private function trend(int $percent): string
    {
        return ($percent > 0 ? '+' : '')."{$percent}%";
    }

    private function tone(int $percent): string
    {
        return match (true) {
            $percent > 0 => 'success',
            $percent < 0 => 'error',
            default => 'neutral',
        };
    }

    private function seed(string $salt): int
    {
        return crc32("{$this->service->id}{$salt}");
    }
}
