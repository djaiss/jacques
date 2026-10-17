<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Helpers\WeeklyHours;
use App\Models\Location;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Give the opening hours of a location, as they are stored, to the people
 * who can be booked there and follow them. Somebody with hours of their own
 * keeps them, unless the author asks to override them, and then follows the
 * opening hours from now on.
 */
class ApplyOpeningHoursToTeam
{
    public function __construct(
        private readonly User $author,
        private readonly Location $location,
        private readonly bool $overrideCustomHours,
    ) {}

    /** @return Collection<int, Staff> */
    public function execute(): Collection
    {
        $this->authorize();

        $week = WeeklyHours::fromRows($this->location->openingHours()->get());

        $query = $this->location->staff()
            ->where('staff.is_active', true)
            ->where('staff.is_bookable', true)
            ->wherePivot('is_active', true);

        if (! $this->overrideCustomHours) {
            $query->wherePivot('follows_opening_hours', true);
        }

        $team = $query->get();

        foreach ($team as $staff) {
            new SyncStaffWorkingHours(
                author: $this->author,
                staff: $staff,
                location: $this->location,
                week: $week,
                followsOpeningHours: true,
            )->execute();
        }

        return $team;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::LocationManage)
            ->forLocation($this->location)
            ->authorize();
    }
}
