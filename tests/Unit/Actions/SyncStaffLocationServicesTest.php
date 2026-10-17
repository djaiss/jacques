<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\SyncStaffLocationServicesTest;

use App\Actions\SyncStaffLocationServices;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

it('sets the services a staff member performs at a location', function () {
    Queue::fake();

    $scranton = Location::factory()->create(['name' => 'Scranton']);
    $beardTrim = offer($scranton);
    $shave = offer($scranton);
    $haircut = offer($scranton);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
    $jim->locations()->attach($scranton->id);
    $jim->locationServices()->attach($haircut->id);

    $result = new SyncStaffLocationServices(
        author: $michael,
        staff: $jim,
        location: $scranton,
        locationServiceIds: [$beardTrim->id, $shave->id],
    )->execute();

    expect($result)->toBeInstanceOf(Staff::class);
    expect($jim->locationServices()->pluck('location_services.id')->all())->toEqualCanonicalizing([$beardTrim->id, $shave->id]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffServicesUpdated
            && $job->parameters === ['name' => 'Jim Halpert', 'location' => 'Scranton'],
    );
});

it('keeps the services performed at another location', function () {
    Queue::fake();

    $scranton = Location::factory()->create();
    $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
    $atStamford = offer($stamford);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach([$scranton->id, $stamford->id]);
    $jim->locationServices()->attach($atStamford->id);

    new SyncStaffLocationServices(author: $michael, staff: $jim, location: $scranton, locationServiceIds: [])->execute();

    expect($jim->locationServices()->pluck('location_services.id')->all())->toEqual([$atStamford->id]);
});

it('throws when the staff member is not assigned to the location', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);

    new SyncStaffLocationServices(author: $michael, staff: $jim, location: $scranton, locationServiceIds: [])->execute();
})->throws(InvalidArgumentException::class);

it('throws when a service is not offered at the location', function () {
    $scranton = Location::factory()->create();
    $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
    $atStamford = offer($stamford);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
    $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
    $jim->locations()->attach($scranton->id);

    new SyncStaffLocationServices(author: $michael, staff: $jim, location: $scranton, locationServiceIds: [$atStamford->id])->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);

    new SyncStaffLocationServices(author: $michael, staff: Staff::factory()->create(), location: $scranton, locationServiceIds: [])->execute();
})->throws(ModelNotFoundException::class);

function offer(Location $location): LocationService
{
    $service = Service::factory()->create(['company_id' => $location->company_id]);

    return LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $service->id]);
}
