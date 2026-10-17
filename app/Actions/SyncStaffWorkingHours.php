<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\WeeklyHours;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Set the whole week a staff member works at one location at once, and
 * whether that week is the opening hours of the location or hours of their
 * own. Every range they held there is replaced, and their hours at other
 * locations stay. They have to be assigned to the location.
 */
class SyncStaffWorkingHours
{
    /**
     * @param  array<int, list<array{start: string, end: string}>>  $week
     */
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly Location $location,
        private readonly array $week,
        private readonly bool $followsOpeningHours,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->validate();
        $this->sync();
        $this->log();

        return $this->staff;
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

        WeeklyHours::validate($this->week);
    }

    private function sync(): void
    {
        StaffWorkingHour::query()
            ->where('staff_id', $this->staff->id)
            ->where('location_id', $this->location->id)
            ->delete();

        foreach ($this->week as $day => $ranges) {
            foreach ($ranges as $range) {
                StaffWorkingHour::query()->create([
                    'staff_id' => $this->staff->id,
                    'location_id' => $this->location->id,
                    'day_of_week' => $day,
                    'start_time' => "{$range['start']}:00",
                    'end_time' => "{$range['end']}:00",
                ]);
            }
        }

        $this->staff->locations()->updateExistingPivot($this->location->id, [
            'follows_opening_hours' => $this->followsOpeningHours,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffWorkingHoursUpdated,
            parameters: [
                'name' => $this->staff->name,
                'location' => $this->location->name,
            ],
        )->onQueue('low');
    }
}
