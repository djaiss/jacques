<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Set every service a staff member performs at one location at once: the
 * ones given are added, the others at that location are removed, and their
 * services at other locations stay. Their assignment to the location has to
 * be active, and the location has to offer each service given.
 */
class SyncStaffLocationServices
{
    /**
     * @param  array<int, int>  $locationServiceIds
     */
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly Location $location,
        private readonly array $locationServiceIds,
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
            ->permission(PermissionEnum::ServiceManage)
            ->forLocation($this->location)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->staff->company_id !== $this->location->company_id) {
            throw new ModelNotFoundException('Staff not found');
        }

        $assigned = $this->staff->activeLocations()
            ->whereKey($this->location->id)
            ->exists();

        if (! $assigned) {
            throw new InvalidArgumentException('A staff member can only perform a service at a location where their assignment is active');
        }

        $offered = LocationService::query()
            ->where('location_id', $this->location->id)
            ->where('is_active', true)
            ->whereHas('service', fn ($query) => $query->where('is_active', true))
            ->whereIn('id', $this->locationServiceIds)
            ->count();

        if ($offered !== count(array_unique($this->locationServiceIds))) {
            throw new ModelNotFoundException('Location service not found');
        }
    }

    private function sync(): void
    {
        $current = $this->staff->locationServices()
            ->where('location_id', $this->location->id)
            ->pluck('location_services.id')
            ->all();

        $this->staff->locationServices()->detach(array_diff($current, $this->locationServiceIds));
        $this->staff->locationServices()->syncWithoutDetaching($this->locationServiceIds);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::StaffServicesUpdated,
            parameters: [
                'name' => $this->staff->name,
                'location' => $this->location->name,
            ],
        )->onQueue('low');
    }
}
