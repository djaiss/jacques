<?php

declare(strict_types=1);

use App\Actions\CreateLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('offers a service at a location with its own price', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton']);
    $haircut = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut'], 'prices' => ['CAD' => 3500]]);

    $locationService = new CreateLocationService(author: $michael, location: $scranton, service: $haircut, prices: ['CAD' => 4000])->execute();

    expect($locationService)->toBeInstanceOf(LocationService::class);
    $this->assertDatabaseHas('location_services', [
        'id' => $locationService->id,
        'location_id' => $scranton->id,
        'service_id' => $haircut->id,
        'is_active' => true,
    ]);
    expect($locationService->refresh()->prices)->toEqual(['CAD' => 4000]);
    expect($locationService->resolvedPrice('CAD'))->toEqual(4000);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationServiceCreated
            && $job->parameters === ['name' => 'Haircut', 'location' => 'Scranton'],
    );
});

it('lets a location follow the price of the service', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $stamford = Location::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->create(['company_id' => $company->id, 'prices' => ['CAD' => 3500]]);

    $locationService = new CreateLocationService(author: $michael, location: $stamford, service: $haircut)->execute();

    $haircut->update(['prices' => ['CAD' => 3800]]);

    expect($locationService->prices)->toBeNull();
    expect($locationService->refresh()->resolvedPrice('CAD'))->toEqual(3800);
});

it('lets a manager offer a service at their location', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $dwight->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);
    $haircut = Service::factory()->create(['company_id' => $company->id]);

    new CreateLocationService(author: $dwight, location: $scranton, service: $haircut)->execute();

    $this->assertDatabaseHas('location_services', ['location_id' => $scranton->id, 'service_id' => $haircut->id]);
});

it('throws when a manager offers a service at another location', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $stamford = Location::factory()->create(['company_id' => $company->id]);
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $dwight->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);
    $haircut = Service::factory()->create(['company_id' => $company->id]);

    new CreateLocationService(author: $dwight, location: $stamford, service: $haircut)->execute();
})->throws(ModelNotFoundException::class);

it('throws when a receptionist offers a service', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::RECEPTIONIST);
    Staff::factory()->create(['company_id' => $pam->company_id, 'user_id' => $pam->id])->locations()->attach($scranton->id);
    $haircut = Service::factory()->create(['company_id' => $company->id]);

    new CreateLocationService(author: $pam, location: $scranton, service: $haircut)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the service belongs to another company', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $scranton = Location::factory()->create(['company_id' => $company->id]);

    new CreateLocationService(author: $michael, location: $scranton, service: Service::factory()->create())->execute();
})->throws(ModelNotFoundException::class);

it('throws when the service is deactivated', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->inactive()->create(['company_id' => $company->id]);

    new CreateLocationService(author: $michael, location: $scranton, service: $haircut)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the location already offers the service', function () {
    $locationService = LocationService::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

    new CreateLocationService(author: $michael, location: $locationService->location, service: $locationService->service)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the price is negative', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->create(['company_id' => $company->id]);

    new CreateLocationService(author: $michael, location: $scranton, service: $haircut, prices: ['CAD' => -100])->execute();
})->throws(InvalidArgumentException::class);

it('throws when a price is in a currency the company does not use', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $haircut = Service::factory()->create(['company_id' => $company->id]);

    new CreateLocationService(author: $michael, location: $scranton, service: $haircut, prices: ['CHF' => 4000])->execute();
})->throws(InvalidArgumentException::class, 'CHF is not a currency of the company');
