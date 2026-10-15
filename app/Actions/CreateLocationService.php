<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Offer a service of the catalogue at a location, optionally at prices of its
 * own in some currencies. In any other currency the location follows the price
 * of the service, including when it changes later.
 */
class CreateLocationService
{
    private LocationService $locationService;

    /** @param  array<string, int>  $prices */
    public function __construct(
        private readonly User $author,
        private readonly Location $location,
        private readonly Service $service,
        private readonly array $prices = [],
    ) {}

    public function execute(): LocationService
    {
        $this->authorize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->locationService;
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
        if ($this->service->company_id !== $this->location->company_id) {
            throw new ModelNotFoundException('Service not found');
        }

        if (! $this->service->is_active) {
            throw new InvalidArgumentException('A deactivated service cannot be offered at a location');
        }

        foreach ($this->prices as $currency => $amount) {
            if (! in_array($currency, $this->location->company->currencies, true)) {
                throw new InvalidArgumentException("{$currency} is not a currency of the company");
            }

            if ($amount < 0) {
                throw new InvalidArgumentException('A price cannot be negative');
            }
        }

        $offered = LocationService::query()
            ->where('location_id', $this->location->id)
            ->where('service_id', $this->service->id)
            ->exists();

        if ($offered) {
            throw new InvalidArgumentException('The location already offers the service called '.$this->service->localizedName($this->service->company->languages[0]));
        }
    }

    private function create(): void
    {
        $this->locationService = LocationService::query()->create([
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'prices' => $this->prices === [] ? null : $this->prices,
            'is_active' => true,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationServiceCreated,
            parameters: [
                'name' => $this->service->localizedName($this->service->company->languages[0]),
                'location' => $this->location->name,
            ],
        )->onQueue('low');
    }
}
