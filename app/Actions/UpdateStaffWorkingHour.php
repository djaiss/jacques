<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffWorkingHour;
use App\Models\User;
use InvalidArgumentException;

/**
 * Move a weekly working range to another day or other times, at the same
 * location. The same rules as when it was added apply: it ends after it
 * starts, and it does not overlap another range of the same day.
 */
class UpdateStaffWorkingHour
{
    private string $start;

    private string $end;

    public function __construct(
        private readonly User $author,
        private readonly StaffWorkingHour $workingHour,
        private readonly int $dayOfWeek,
        private readonly string $startTime,
        private readonly string $endTime,
    ) {}

    public function execute(): StaffWorkingHour
    {
        $this->authorize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->workingHour;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ScheduleManage)
            ->forStaff($this->workingHour->staff)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->dayOfWeek < 0 || $this->dayOfWeek > 6) {
            throw new InvalidArgumentException('The day of the week has to be between 0 for Sunday and 6 for Saturday');
        }

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $this->startTime) !== 1) {
            throw new InvalidArgumentException('A working range starts at a time written as HH:MM');
        }

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $this->endTime) !== 1) {
            throw new InvalidArgumentException('A working range ends at a time written as HH:MM');
        }

        $this->start = "{$this->startTime}:00";
        $this->end = "{$this->endTime}:00";

        if ($this->end <= $this->start) {
            throw new InvalidArgumentException('A working range has to end after it starts');
        }

        $overlaps = StaffWorkingHour::query()
            ->whereKeyNot($this->workingHour->id)
            ->where('staff_id', $this->workingHour->staff_id)
            ->where('location_id', $this->workingHour->location_id)
            ->where('day_of_week', $this->dayOfWeek)
            ->where('start_time', '<', $this->end)
            ->where('end_time', '>', $this->start)
            ->exists();

        if ($overlaps) {
            throw new InvalidArgumentException('A working range cannot overlap another range on the same day');
        }
    }

    private function update(): void
    {
        $this->workingHour->day_of_week = $this->dayOfWeek;
        $this->workingHour->start_time = $this->start;
        $this->workingHour->end_time = $this->end;
        $this->workingHour->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->workingHour->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffWorkingHourUpdated,
            parameters: [
                'name' => $this->workingHour->staff->name,
                'location' => $this->workingHour->location->name,
            ],
        )->onQueue('low');
    }
}
