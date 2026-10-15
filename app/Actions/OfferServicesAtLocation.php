<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Offer every active service of a company at one of its locations, at the
 * price of the service, skipping the ones the location already offers. It
 * returns what it offered, which is nothing when everything already was.
 */
class OfferServicesAtLocation
{
    public function __construct(
        private readonly Location $location,
    ) {}

    /** @return Collection<int, LocationService> */
    public function execute(): Collection
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

        return $services->map(fn (Service $service): LocationService => LocationService::query()->create([
            'location_id' => $this->location->id,
            'service_id' => $service->id,
            'prices' => null,
            'is_active' => true,
        ]))->toBase();
    }
}
