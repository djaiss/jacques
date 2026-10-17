<?php

declare(strict_types=1);

use App\Models\LocationService;
use App\Models\Staff;
use App\Models\StaffLocationService;

it('belongs to a staff member', function () {
    $staff = Staff::factory()->create();
    $assignment = StaffLocationService::factory()->create(['staff_id' => $staff->id]);

    expect($assignment->staff()->exists())->toBeTrue();
    expect($assignment->staff->id)->toEqual($staff->id);
});

it('belongs to a location service', function () {
    $locationService = LocationService::factory()->create();
    $assignment = StaffLocationService::factory()->create(['location_service_id' => $locationService->id]);

    expect($assignment->locationService()->exists())->toBeTrue();
    expect($assignment->locationService->id)->toEqual($locationService->id);
});
