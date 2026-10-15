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

/**
 * Stop a staff member performing a service at a location. Removing somebody who
 * never performed it there changes nothing.
 */
class RemoveStaffLocationService
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
        $this->remove();
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
    }

    private function remove(): void
    {
        $this->staff->locationServices()->detach($this->locationService->id);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->locationService->location->company,
            user: $this->author,
            action: UserActionEnum::StaffLocationServiceRemoved,
            parameters: [
                'name' => $this->locationService->service->localizedName($this->locationService->service->company->languages[0]),
                'location' => $this->locationService->location->name,
                'staff' => $this->staff->name,
            ],
        )->onQueue('low');
    }
}
