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
 * Change the prices of a service at a location, or stop and resume offering it
 * there. A currency without a price makes the location follow the price of the
 * service in that currency again.
 */
class UpdateLocationService
{
    /** @param  array<string, int>  $prices */
    public function __construct(
        private readonly User $author,
        private readonly LocationService $locationService,
        private readonly array $prices = [],
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
        foreach ($this->prices as $currency => $amount) {
            if (! in_array($currency, $this->locationService->location->company->currencies, true)) {
                throw new InvalidArgumentException("{$currency} is not a currency of the company");
            }

            if ($amount < 0) {
                throw new InvalidArgumentException('A price cannot be negative');
            }
        }
    }

    private function update(): void
    {
        $removedCurrencies = array_diff_key($this->locationService->prices ?? [], array_flip($this->locationService->location->company->currencies));
        $prices = [...$removedCurrencies, ...$this->prices];
        $this->locationService->prices = $prices === [] ? null : $prices;
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
