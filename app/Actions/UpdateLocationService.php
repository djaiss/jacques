<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LocationService;
use App\Models\User;
use InvalidArgumentException;

/**
 * Change the price of a service at a location, or stop and resume offering it
 * there. A null price makes the location follow the price of the service again.
 */
class UpdateLocationService
{
    public function __construct(
        private readonly User $author,
        private readonly LocationService $locationService,
        private readonly ?int $price = null,
        private readonly bool $isActive = true,
    ) {}

    public function execute(): LocationService
    {
        $this->authorize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->locationService;
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
        if ($this->price !== null && $this->price < 0) {
            throw new InvalidArgumentException('A price cannot be negative');
        }
    }

    private function update(): void
    {
        $this->locationService->price = $this->price;
        $this->locationService->is_active = $this->isActive;
        $this->locationService->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->locationService->location->company,
            user: $this->author,
            action: UserActionEnum::LocationServiceUpdated,
            parameters: [
                'name' => $this->locationService->service->localizedName($this->locationService->service->company->languages[0]),
                'location' => $this->locationService->location->name,
            ],
        )->onQueue('low');
    }
}
