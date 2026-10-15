<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;

/**
 * Offer every active service of a company at one of its locations, at the
 * price of the service, skipping the ones the location already offers.
 */
class OfferServicesAtLocation
{
    public function __construct(
        private readonly Location $location,
    ) {}

    public function execute(): void
    {
        $offered = LocationService::query()
            ->where('location_id', $this->location->id)
            ->pluck('service_id');

        $services = Service::query()
            ->where('company_id', $this->location->company_id)
            ->where('is_active', true)
            ->whereNotIn('id', $offered)
            ->orderBy('id')
            ->get();

        foreach ($services as $service) {
            LocationService::query()->create([
                'location_id' => $this->location->id,
                'service_id' => $service->id,
                'prices' => null,
                'is_active' => true,
            ]);
        }
    }
}
