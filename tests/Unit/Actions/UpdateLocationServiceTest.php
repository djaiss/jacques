<?php

declare(strict_types=1);

use App\Actions\UpdateLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('changes the price and stops offering the service at one location only', function () {
    Queue::fake();

    $scranton = Location::factory()->create(['name' => 'Scranton']);
    $atScranton = LocationService::factory()->create(['location_id' => $scranton->id]);
    $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
    $atStamford = LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $atScranton->service_id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);

    new UpdateLocationService(author: $michael, locationService: $atScranton, prices: ['CAD' => 4000], isActive: false)->execute();

    expect($atScranton->refresh()->prices)->toEqual(['CAD' => 4000]);
    expect($atScranton->is_active)->toBeFalse();
    expect($atStamford->refresh()->is_active)->toBeTrue();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationServiceUpdated
            && $job->parameters === ['name' => $atScranton->service->name['en'], 'location' => 'Scranton'],
    );
});

it('clears the price so the location follows the service again', function () {
    Queue::fake();

    $locationService = LocationService::factory()->create(['prices' => ['CAD' => 4000]]);
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

    new UpdateLocationService(author: $michael, locationService: $locationService, prices: [])->execute();

    expect($locationService->refresh()->prices)->toBeNull();
    expect($locationService->resolvedPrice('CAD'))->toEqual($locationService->service->prices['CAD']);
});

it('throws when a price is in a currency the company does not use', function () {
    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

    new UpdateLocationService(author: $michael, locationService: $locationService, prices: ['USD' => 4000])->execute();
})->throws(InvalidArgumentException::class, 'USD is not a currency of the company');

it('throws when the price is negative', function () {
    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

    new UpdateLocationService(author: $michael, locationService: $locationService, prices: ['CAD' => -1])->execute();
})->throws(InvalidArgumentException::class);

it('throws when a manager changes another location', function () {
    $atStamford = LocationService::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $dwight->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);

    new UpdateLocationService(author: $dwight, locationService: $atStamford, prices: ['CAD' => 4000])->execute();
})->throws(ModelNotFoundException::class);

it('throws when the location belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new UpdateLocationService(author: $michael, locationService: LocationService::factory()->create(), prices: ['CAD' => 4000])->execute();
})->throws(ModelNotFoundException::class);
