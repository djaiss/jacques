<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\LocationService;
use App\Models\LocationStaff;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;

it('belongs to a company', function () {
    $company = Company::factory()->create();
    $location = Location::factory()->create(['company_id' => $company->id]);

    expect($location->company()->exists())->toBeTrue();
    expect($location->company->id)->toEqual($company->id);
});

it('belongs to many staff members with the state of each assignment', function () {
    $location = Location::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $location->company_id]);
    LocationStaff::factory()->paused()->create(['location_id' => $location->id, 'staff_id' => $dwight->id]);

    expect($location->staff()->exists())->toBeTrue();
    expect($location->staff->first()->id)->toEqual($dwight->id);
    expect((bool) $location->staff->first()->pivot->is_active)->toBeFalse();
});

it('has many location services', function () {
    $location = Location::factory()->create();
    LocationService::factory()->create(['location_id' => $location->id]);

    expect($location->locationServices()->exists())->toBeTrue();
});

it('has many opening hours', function () {
    $location = Location::factory()->create();
    LocationOpeningHour::factory()->create(['location_id' => $location->id]);

    expect($location->openingHours()->exists())->toBeTrue();
});

it('tells whether each staff member follows its opening hours', function () {
    $location = Location::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $location->company_id]);
    LocationStaff::factory()->withCustomHours()->create(['location_id' => $location->id, 'staff_id' => $jim->id]);

    expect((bool) $location->staff->first()->pivot->follows_opening_hours)->toBeFalse();
});

it('belongs to many services', function () {
    $location = Location::factory()->create();
    $haircut = Service::factory()->create(['company_id' => $location->company_id, 'name' => ['en' => 'Haircut']]);
    $location->services()->attach($haircut->id);

    expect($location->services()->exists())->toBeTrue();
    expect($location->services->first()->name)->toEqual(['en' => 'Haircut']);
    expect((bool) $location->services->first()->pivot->is_active)->toBeTrue();
});

it('knows whether it has been archived', function () {
    $open = Location::factory()->make();
    $closed = Location::factory()->archived()->make();

    expect($open->isArchived())->toBeFalse();
    expect($closed->isArchived())->toBeTrue();
});

it('casts the day it was archived and the headquarters flag', function () {
    $location = Location::factory()->archived()->primary()->create();

    expect($location->archived_at)->toBeInstanceOf(Carbon::class);
    expect($location->is_headquarters)->toBeBool();
});
