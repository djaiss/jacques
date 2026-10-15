<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LocationService;
use App\Models\User;

/**
 * Stop offering a service at a location for good. Who performed it there is
 * forgotten with it; the service itself stays in the catalogue.
 */
class DestroyLocationService
{
    public function __construct(
        private readonly User $author,
        private readonly LocationService $locationService,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->destroy();
        $this->log();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forLocation($this->locationService->location)
            ->authorize();
    }

    private function destroy(): void
    {
        $this->locationService->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->locationService->location->company,
            user: $this->author,
            action: UserActionEnum::LocationServiceDeleted,
            parameters: [
                'name' => $this->locationService->service->localizedName($this->locationService->service->company->languages[0]),
                'location' => $this->locationService->location->name,
            ],
        )->onQueue('low');
    }
}
