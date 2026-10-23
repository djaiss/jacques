<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\SyncServiceOffersTest;

use App\Actions\SyncServiceOffers;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

function owner(): User
{
    $company = Company::factory()->create(['currencies' => ['CAD', 'USD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function staffAt(Location $location, string $name): Staff
{
    $staff = Staff::factory()->create(['company_id' => $location->company_id, 'display_name' => $name]);
    $staff->locations()->attach($location->id);

    return $staff;
}

it('offers a service where it is ticked, by the people ticked there', function () {
    Queue::fake();

    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $pam = staffAt($scranton, 'Pam Beesly');
    $phyllis = staffAt($scranton, 'Phyllis Vance');
    $balayage = Service::factory()->create(['company_id' => $michael->company_id]);

    $result = new SyncServiceOffers(author: $michael, service: $balayage, offers: [$scranton->id => [$pam->id, $phyllis->id]])->execute();

    expect($result)->toBeInstanceOf(Service::class);
    $offer = LocationService::query()->where('location_id', $scranton->id)->where('service_id', $balayage->id)->sole();
    expect($offer->is_active)->toBeTrue();
    expect($offer->staff()->pluck('staff.id')->sort()->values()->all())->toEqual([$pam->id, $phyllis->id]);
    $this->assertDatabaseMissing('location_services', ['location_id' => $stamford->id, 'service_id' => $balayage->id]);
});

it('pauses a location left out, keeps its prices and lets its people go', function () {
    Queue::fake();

    $michael = owner();
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $karen = staffAt($stamford, 'Karen Filippelli');
    $balayage = Service::factory()->create(['company_id' => $michael->company_id]);
    $offer = LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id, 'prices' => ['CAD' => 19000]]);
    $offer->staff()->attach($karen->id);

    new SyncServiceOffers(author: $michael, service: $balayage, offers: [])->execute();

    $offer->refresh();
    expect($offer->is_active)->toBeFalse();
    expect($offer->prices)->toEqual(['CAD' => 19000]);
    expect($offer->staff()->count())->toBe(0);
});

it('offers a paused location again', function () {
    Queue::fake();

    $michael = owner();
    $stamford = Location::factory()->create(['company_id' => $michael->company_id]);
    $karen = staffAt($stamford, 'Karen Filippelli');
    $balayage = Service::factory()->create(['company_id' => $michael->company_id]);
    $offer = LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id, 'is_active' => false]);

    new SyncServiceOffers(author: $michael, service: $balayage, offers: [$stamford->id => [$karen->id]])->execute();

    $offer->refresh();
    expect($offer->is_active)->toBeTrue();
    expect($offer->staff()->pluck('staff.id')->all())->toEqual([$karen->id]);
});

it('lets go of somebody unticked and keeps the others', function () {
    Queue::fake();

    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);
    $pam = staffAt($scranton, 'Pam Beesly');
    $jim = staffAt($scranton, 'Jim Halpert');
    $haircut = Service::factory()->create(['company_id' => $michael->company_id]);
    $offer = LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id]);
    $offer->staff()->attach([$pam->id, $jim->id]);

    new SyncServiceOffers(author: $michael, service: $haircut, offers: [$scranton->id => [$jim->id]])->execute();

    expect($offer->staff()->pluck('staff.id')->all())->toEqual([$jim->id]);
});

it('refuses somebody who does not work at the location', function () {
    Queue::fake();

    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);
    $nashua = Location::factory()->create(['company_id' => $michael->company_id]);
    $holly = staffAt($nashua, 'Holly Flax');
    $haircut = Service::factory()->create(['company_id' => $michael->company_id]);

    new SyncServiceOffers(author: $michael, service: $haircut, offers: [$scranton->id => [$holly->id]])->execute();
})->throws(InvalidArgumentException::class);

it('refuses a location of another company', function () {
    $michael = owner();
    $elsewhere = Location::factory()->create();
    $haircut = Service::factory()->create(['company_id' => $michael->company_id]);

    new SyncServiceOffers(author: $michael, service: $haircut, offers: [$elsewhere->id => []])->execute();
})->throws(ModelNotFoundException::class);

it('refuses somebody of another company', function () {
    Queue::fake();

    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);
    $stranger = Staff::factory()->create();
    $haircut = Service::factory()->create(['company_id' => $michael->company_id]);

    new SyncServiceOffers(author: $michael, service: $haircut, offers: [$scranton->id => [$stranger->id]])->execute();
})->throws(ModelNotFoundException::class);

it('refuses somebody who may not change the services', function () {
    $company = Company::factory()->create();
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->create(['company_id' => $company->id]);

    new SyncServiceOffers(author: $dwight, service: $haircut, offers: [])->execute();
})->throws(ModelNotFoundException::class);
