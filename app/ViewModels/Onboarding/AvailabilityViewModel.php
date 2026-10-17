<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Helpers\WeeklyHours;
use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

class AvailabilityViewModel extends StepViewModel
{
    /**
     * The days of the week as the screen lists them, Monday first, each the
     * number it is stored with, from 0 for Sunday.
     */
    public const array DAYS_MONDAY_FIRST = [1, 2, 3, 4, 5, 6, 0];

    /**
     * The week offered to a salon that has not set its opening hours yet.
     *
     * @var array<int, list<array{start: string, end: string}>>
     */
    public const array DEFAULT_WEEK = [
        0 => [],
        1 => [['start' => '09:00', 'end' => '18:00']],
        2 => [['start' => '09:00', 'end' => '18:00']],
        3 => [['start' => '09:00', 'end' => '18:00']],
        4 => [['start' => '09:00', 'end' => '20:00']],
        5 => [['start' => '09:00', 'end' => '20:00']],
        6 => [['start' => '09:00', 'end' => '17:00']],
    ];

    private ?Location $headquarters = null;

    /** @var Collection<int, Staff>|null */
    private ?Collection $team = null;

    /** @var array<int, bool>|null */
    private ?array $followers = null;

    public function __construct(
        User $user,
        OnboardingStepEnum $step,
        private readonly ?Staff $openStaff = null,
    ) {
        parent::__construct(user: $user, step: $step);
    }

    public function title(): string
    {
        return __('When can clients come in?');
    }

    public function introduction(): string
    {
        return __('Appointments can only be booked when the salon is open and somebody is working. We filled in common hours. Change anything that does not match your week.');
    }

    public function openingHoursHelp(): string
    {
        return __('The hours :salon is usually open. Holidays and one-off closures are added later from Settings.', ['salon' => $this->salonName()]);
    }

    /**
     * What the opening hours editor in the browser starts from: the stored
     * week, or the default one, Monday first, and every sentence it writes
     * itself.
     *
     * @return array<string, mixed>
     */
    public function editor(): array
    {
        $stored = $this->loadedHeadquarters()->openingHours;
        $week = $stored->isEmpty() ? self::DEFAULT_WEEK : WeeklyHours::fromRows($stored);

        return [
            'days' => $this->days($week),
            'times' => array_map(fn (string $time): array => [
                'value' => $time,
                'label' => $this->timeLabel($time),
            ], WeeklyHours::grid()),
            'locale' => str_replace('_', '-', app()->getLocale()),
            'sentences' => [
                'summaries' => array_map(fn (int $count): string => $count === 0
                    ? __('Closed all week')
                    : trans_choice(':count day, :hours h a week|:count days, :hours h a week', $count), range(0, 7)),
                'dayHours' => __(':hours h'),
                'endBeforeStart' => __('The end has to come after the start.'),
                'overlap' => __('These hours overlap.'),
                'outsideOpeningHours' => __('Partly outside opening hours. Clients can only book while the salon is open.'),
                'salonClosed' => __('The salon is closed on :day. Clients cannot book this day.'),
                'salonHours' => __('Salon :hours'),
                'salonClosedShort' => __('Salon closed'),
                'range' => __(':start to :end'),
            ],
        ];
    }

    public function isUsingOpeningHours(): bool
    {
        return $this->customCount() === 0;
    }

    public function useOpeningHoursHelp(): string
    {
        return __('Everybody works whenever the salon is open, and their hours follow if you change the opening hours. Untick this to give somebody their own hours.');
    }

    public function customHoursHelp(): string
    {
        return __('Somebody without custom hours still follows the opening hours.');
    }

    /** @return list<array<string, mixed>> */
    public function teamRows(): array
    {
        return $this->loadedTeam()->values()->map(fn (Staff $staff, int $index): array => [
            'id' => $staff->id,
            'name' => $staff->name,
            'jobTitle' => $staff->job_title,
            'hue' => [30, 80, 200, 250, 310][$index % 5],
            'follows' => $this->followsOpeningHours($staff),
            'summary' => $this->customSummary($staff),
            'editUrl' => route('onboarding.availability.staff.edit', $staff),
        ])->all();
    }

    /**
     * What the footer says while the box giving everybody the opening hours
     * is ticked.
     */
    public function teamOnOpeningHoursStatus(): string
    {
        if ($this->loadedTeam()->isEmpty()) {
            return __('Nobody on the team can be booked yet.');
        }

        return trans_choice(':count team member on salon opening hours.|:count team members on salon opening hours.', $this->loadedTeam()->count());
    }

    /**
     * What the footer says while the box is unticked, which depends on how
     * many people have hours of their own.
     */
    public function teamStatus(): string
    {
        if ($this->customCount() === 0) {
            return $this->teamOnOpeningHoursStatus();
        }

        return trans_choice(':count team member, :custom with custom hours.|:count team members, :custom with custom hours.', $this->loadedTeam()->count(), [
            'custom' => $this->customCount(),
        ]);
    }

    public function panel(): ?StaffHoursPanel
    {
        if ($this->openStaff === null) {
            return null;
        }

        $staff = $this->loadedTeam()->firstWhere('id', $this->openStaff->id);

        return new StaffHoursPanel(
            staff: $staff,
            week: $this->days(WeeklyHours::fromRows($staff->workingHours->where('location_id', $this->loadedHeadquarters()->id))),
            follows: $this->followsOpeningHours($staff),
        );
    }

    /**
     * The week as the browser reads it, Monday first. A closed day keeps a
     * range to offer when it is opened again.
     *
     * @param  array<int, list<array{start: string, end: string}>>  $week
     * @return list<array{day: int, short: string, name: string, open: bool, ranges: list<array{start: string, end: string}>}>
     */
    private function days(array $week): array
    {
        $monday = Carbon::now()->locale(app()->getLocale())->startOfWeek(Carbon::MONDAY);

        return array_map(function (int $day) use ($week, $monday): array {
            $date = $monday->copy()->addDays($day === 0 ? 6 : $day - 1);
            $ranges = $week[$day] ?? [];

            return [
                'day' => $day,
                'short' => $date->isoFormat('ddd'),
                'name' => $date->isoFormat('dddd'),
                'open' => $ranges !== [],
                'ranges' => $ranges === [] ? [['start' => '09:00', 'end' => '18:00']] : $ranges,
            ];
        }, self::DAYS_MONDAY_FIRST);
    }

    private function customSummary(Staff $staff): string
    {
        $ranges = $staff->workingHours->where('location_id', $this->loadedHeadquarters()->id);

        if ($ranges->isEmpty()) {
            return __('No working hours yet');
        }

        $minutes = $ranges->sum(fn ($range): int => (int) ((strtotime($range->end_time) - strtotime($range->start_time)) / 60));

        return trans_choice(':count day, :hours h a week|:count days, :hours h a week', $ranges->pluck('day_of_week')->unique()->count(), [
            'hours' => Number::format($minutes / 60, maxPrecision: 1, locale: app()->getLocale()),
        ]);
    }

    private function timeLabel(string $time): string
    {
        return ((int) mb_substr($time, 0, 2)).mb_substr($time, 2);
    }

    private function customCount(): int
    {
        return $this->loadedTeam()->filter(fn (Staff $staff): bool => ! $this->followsOpeningHours($staff))->count();
    }

    private function followsOpeningHours(Staff $staff): bool
    {
        $this->followers ??= LocationStaff::query()
            ->where('location_id', $this->loadedHeadquarters()->id)
            ->pluck('follows_opening_hours', 'staff_id')
            ->map(fn ($follows): bool => (bool) $follows)
            ->all();

        return $this->followers[$staff->id] ?? true;
    }

    private function loadedHeadquarters(): Location
    {
        return $this->headquarters ??= Location::query()
            ->where('company_id', $this->user->company_id)
            ->where('is_headquarters', true)
            ->with('openingHours')
            ->firstOrFail();
    }

    /**
     * The people who can be booked at the headquarters, the author first.
     *
     * @return Collection<int, Staff>
     */
    private function loadedTeam(): Collection
    {
        return $this->team ??= $this->loadedHeadquarters()->staff()
            ->where('staff.is_active', true)
            ->where('staff.is_bookable', true)
            ->wherePivot('is_active', true)
            ->with('workingHours')
            ->orderBy('position')
            ->get()
            ->sortBy(fn (Staff $staff): int => $staff->user_id === $this->user->id ? 0 : 1)
            ->values();
    }
}
