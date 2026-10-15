<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LocationService;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Let a staff member perform a service at a location. Their assignment to the
 * location has to be active, and the location has to offer the service.
 * Assigning somebody who already performs it there changes nothing.
 */
class AssignStaffLocationService
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly LocationService $locationService,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->validate();
        $this->assign();
        $this->log();

        return $this->staff;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forLocation($this->locationService->location)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->staff->company_id !== $this->locationService->location->company_id) {
            throw new ModelNotFoundException('Staff not found');
        }

        $assigned = $this->staff->activeLocations()
            ->whereKey($this->locationService->location_id)
            ->exists();

        if (! $assigned) {
            throw new InvalidArgumentException('A staff member can only perform a service at a location where their assignment is active');
        }

        if (! $this->locationService->is_active || ! $this->locationService->service->is_active) {
            throw new InvalidArgumentException('The location does not offer the service called '.$this->locationService->service->localizedName($this->locationService->service->company->languages[0]));
        }
    }

    private function assign(): void
    {
        $this->staff->locationServices()->syncWithoutDetaching([$this->locationService->id]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->locationService->location->company,
            user: $this->author,
            action: UserActionEnum::StaffLocationServiceAssigned,
            parameters: [
                'name' => $this->locationService->service->localizedName($this->locationService->service->company->languages[0]),
                'location' => $this->locationService->location->name,
                'staff' => $this->staff->name,
            ],
        )->onQueue('low');
    }
}
