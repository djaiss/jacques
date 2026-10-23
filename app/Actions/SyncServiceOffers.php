<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Set at once the locations that offer a service and who performs it at each.
 * A location left out stops offering it but keeps its own prices, for the day it
 * offers the service again; the people who performed it there are let go.
 */
class SyncServiceOffers
{
    /** @param  array<int, list<int>>  $offers */
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
        private readonly array $offers,
    ) {}

    public function execute(): Service
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            foreach ($this->service->company->locations()->whereNull('archived_at')->get() as $location) {
                $this->sync($location);
            }
        });

        return $this->service->refresh();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->service->company)
            ->authorize();
    }

    private function validate(): void
    {
        $locationIds = $this->service->company->locations()->whereNull('archived_at')->pluck('id')->all();

        foreach (array_keys($this->offers) as $locationId) {
            if (! in_array($locationId, $locationIds, true)) {
                throw new ModelNotFoundException('Location not found');
            }
        }
    }

    private function sync(Location $location): void
    {
        $locationService = LocationService::query()
            ->where('location_id', $location->id)
            ->where('service_id', $this->service->id)
            ->first();

        if (! array_key_exists($location->id, $this->offers)) {
            if ($locationService === null || ! $locationService->is_active) {
                return;
            }

            $this->removeEverybody($locationService);

            new UpdateLocationService(
                author: $this->author,
                locationService: $locationService,
                prices: $locationService->prices ?? [],
                isActive: false,
            )->execute();

            return;
        }

        if ($locationService === null) {
            $locationService = new CreateLocationService(
                author: $this->author,
                location: $location,
                service: $this->service,
            )->execute();
        } elseif (! $locationService->is_active) {
            $locationService = new UpdateLocationService(
                author: $this->author,
                locationService: $locationService,
                prices: $locationService->prices ?? [],
                isActive: true,
            )->execute();
        }

        $this->syncStaff($locationService, $this->offers[$location->id]);
    }

    /** @param  list<int>  $staffIds */
    private function syncStaff(LocationService $locationService, array $staffIds): void
    {
        $performing = $locationService->staff()->pluck('staff.id')->all();

        foreach ($staffIds as $staffId) {
            if (in_array($staffId, $performing, true)) {
                continue;
            }

            $staff = Staff::query()
                ->where('company_id', $this->service->company_id)
                ->findOrFail($staffId);

            new AssignStaffLocationService(
                author: $this->author,
                staff: $staff,
                locationService: $locationService,
            )->execute();
        }

        foreach (array_diff($performing, $staffIds) as $staffId) {
            new RemoveStaffLocationService(
                author: $this->author,
                staff: Staff::query()->findOrFail($staffId),
                locationService: $locationService,
            )->execute();
        }
    }

    private function removeEverybody(LocationService $locationService): void
    {
        foreach ($locationService->staff as $staff) {
            new RemoveStaffLocationService(
                author: $this->author,
                staff: $staff,
                locationService: $locationService,
            )->execute();
        }
    }
}
