<?php

declare(strict_types=1);

namespace App\ViewModels\Dashboard;

use Carbon\CarbonImmutable;

class DashboardViewModel
{
    /*
     * The browser adds and removes appointments without asking the server, so
     * it is handed every count it may have to write, up to this margin over
     * what the day holds.
     */
    private const int COUNT_MARGIN = 50;

    private readonly GeneratedAgenda $agenda;

    private ?DayAgenda $day = null;

    public function __construct(
        private readonly CarbonImmutable $date,
        private readonly CarbonImmutable $now,
        private readonly ?CarbonImmutable $month = null,
    ) {
        $this->agenda = new GeneratedAgenda;
    }

    public function title(): string
    {
        return $this->date->isoFormat('dddd D MMMM YYYY');
    }

    public function isToday(): bool
    {
        return $this->date->isSameDay($this->now);
    }

    public function isClosed(): bool
    {
        return $this->date->isSunday();
    }

    public function summary(): string
    {
        if ($this->isClosed()) {
            return __('Salon closed');
        }

        $count = $this->visibleCount();

        return $this->isToday()
            ? trans_choice('Today, :count appointment in total|Today, :count appointments in total', $count)
            : trans_choice(':count appointment in total|:count appointments in total', $count);
    }

    public function day(): DayAgenda
    {
        return $this->day ??= new DayAgenda(date: $this->date, agenda: $this->agenda);
    }

    public function calendar(): MonthCalendar
    {
        return new MonthCalendar(
            selected: $this->date,
            month: ($this->month ?? $this->date)->startOfMonth(),
            today: $this->now,
        );
    }

    /**
     * @return list<array{id: string, name: string, hue: int, visible: bool}>
     */
    public function hairdressers(): array
    {
        return $this->agenda->hairdressers();
    }

    /**
     * @return list<array{name: string, duration: int}>
     */
    public function services(): array
    {
        return $this->agenda->services();
    }

    public function showsCurrentTime(): bool
    {
        $minutes = $this->now->hour * 60 + $this->now->minute;

        return $this->isToday()
            && ! $this->isClosed()
            && $minutes >= GeneratedAgenda::OPENING
            && $minutes <= GeneratedAgenda::CLOSING;
    }

    public function currentTimeTop(): int
    {
        return (int) round(($this->now->hour * 60 + $this->now->minute - GeneratedAgenda::OPENING) * DayAgenda::PIXELS_PER_MINUTE);
    }

    public function currentTimeLabel(): string
    {
        return $this->now->isoFormat('LT');
    }

    public function todayUrl(): string
    {
        return route('dashboard.index');
    }

    public function previousDayUrl(): string
    {
        return route('dashboard.show', ['date' => $this->date->subDay()->format('Y-m-d')]);
    }

    public function nextDayUrl(): string
    {
        return route('dashboard.show', ['date' => $this->date->addDay()->format('Y-m-d')]);
    }

    /**
     * What the agenda in the browser starts from: who is checked, the slot
     * size, the working ranges of each column, and every sentence it writes.
     *
     * @return array<string, mixed>
     */
    public function alpineState(): array
    {
        $columns = collect($this->day()->columns());
        $highest = $columns->sum('count') + self::COUNT_MARGIN;

        return [
            'visible' => collect($this->hairdressers())->where('visible', true)->pluck('id')->values()->all(),
            'slot' => 20,
            'pixelsPerMinute' => DayAgenda::PIXELS_PER_MINUTE,
            'opening' => GeneratedAgenda::OPENING,
            'closing' => GeneratedAgenda::CLOSING,
            'isToday' => $this->isToday(),
            'isClosed' => $this->isClosed(),
            'timezone' => $this->now->timezoneName,
            'locale' => str_replace('_', '-', app()->getLocale()),
            'columns' => $columns->mapWithKeys(fn (array $column): array => [$column['id'] => [
                'name' => $column['name'],
                'count' => $column['count'],
                'absent' => $column['absent'],
                'workingRanges' => $column['workingRanges'],
                'appointments' => array_map(fn (array $appointment): array => [
                    'id' => $appointment['id'],
                    'start' => $appointment['start'],
                    'duration' => $appointment['duration'],
                ], $column['appointments']),
            ]])->all(),
            'services' => $this->services(),
            'countLabels' => array_map(fn (int $count): string => trans_choice(':count appointment|:count appointments', $count), range(0, $highest)),
            'summaryLabels' => array_map(fn (int $count): string => $this->isToday()
                ? trans_choice('Today, :count appointment in total|Today, :count appointments in total', $count)
                : trans_choice(':count appointment in total|:count appointments in total', $count), range(0, $highest)),
            'sentences' => [
                'book' => __('Book at :time with :name'),
                'draftTitle' => __(':time with :name'),
                'draftHelp' => __('On :day. The length follows the chosen service.', ['day' => $this->date->isoFormat('dddd D MMMM')]),
                'range' => __(':start to :end'),
                'serviceDetail' => __(':service, :minutes min'),
                'serviceOption' => __(':service (:minutes min)'),
                'when' => __(':day, :range', ['day' => $this->date->isoFormat('dddd D MMMM'), 'range' => ':range']),
            ],
        ];
    }

    private function visibleCount(): int
    {
        $visible = collect($this->hairdressers())->where('visible', true)->pluck('id');

        return collect($this->day()->columns())->whereIn('id', $visible)->sum('count');
    }
}
