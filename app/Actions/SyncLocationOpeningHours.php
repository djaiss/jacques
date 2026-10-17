<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\WeeklyHours;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\User;

/**
 * Set the whole week a location is open at once: every range it held is
 * replaced by the ranges given, and a day given no range is closed. A range
 * has to end after it starts, and the ranges of a day must not overlap.
 */
class SyncLocationOpeningHours
{
    /**
     * @param  array<int, list<array{start: string, end: string}>>  $week
     */
    public function __construct(
        private readonly User $author,
        private readonly Location $location,
        private readonly array $week,
    ) {}

    public function execute(): Location
    {
        $this->authorize();

        WeeklyHours::validate($this->week);

        $this->sync();
        $this->log();

        return $this->location;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::LocationManage)
            ->forLocation($this->location)
            ->authorize();
    }

    private function sync(): void
    {
        $this->location->openingHours()->delete();

        foreach ($this->week as $day => $ranges) {
            foreach ($ranges as $range) {
                LocationOpeningHour::query()->create([
                    'location_id' => $this->location->id,
                    'day_of_week' => $day,
                    'start_time' => "{$range['start']}:00",
                    'end_time' => "{$range['end']}:00",
                ]);
            }
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationOpeningHoursUpdated,
            parameters: [
                'location' => $this->location->name,
            ],
        )->onQueue('low');
    }
}
