<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Add a weekly time range a staff member works at a location they are
 * assigned to. Several ranges on the same day leave a break between them, so
 * a range has to end after it starts and must not overlap another range of
 * the same staff member, location and day.
 */
class CreateStaffWorkingHour
{
    private StaffWorkingHour $workingHour;

    private string $start;

    private string $end;

    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly Location $location,
        private readonly int $dayOfWeek,
        private readonly string $startTime,
        private readonly string $endTime,
    ) {}

    public function execute(): StaffWorkingHour
    {
        $this->authorize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->workingHour;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ScheduleManage)
            ->forStaff($this->staff)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->location->company_id !== $this->staff->company_id) {
            throw new ModelNotFoundException('Location not found');
        }

        if (! $this->staff->locations()->whereKey($this->location->id)->exists()) {
            throw new InvalidArgumentException('Working hours can only be set at a location the staff member is assigned to');
        }

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
            ->where('staff_id', $this->staff->id)
            ->where('location_id', $this->location->id)
            ->where('day_of_week', $this->dayOfWeek)
            ->where('start_time', '<', $this->end)
            ->where('end_time', '>', $this->start)
            ->exists();

        if ($overlaps) {
            throw new InvalidArgumentException('A working range cannot overlap another range on the same day');
        }
    }

    private function create(): void
    {
        $this->workingHour = StaffWorkingHour::query()->create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => $this->dayOfWeek,
            'start_time' => $this->start,
            'end_time' => $this->end,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffWorkingHourCreated,
            parameters: [
                'name' => $this->staff->name,
                'location' => $this->location->name,
            ],
        )->onQueue('low');
    }
}
