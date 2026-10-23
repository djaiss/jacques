<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Services\ServiceViewModelTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Services\ServiceViewModel;

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD', 'USD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('reads each salon as off, empty or with its people', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Nashua']);
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Pam Beesly', 'is_active' => true]);
    $balayage = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage']]);
    LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $balayage->id])->staff()->attach($pam->id);
    LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id]);

    $salons = collect((new ServiceViewModel(user: $michael, service: $balayage))->salons())->keyBy('name');

    expect($salons['Scranton']['people'][0]['name'])->toBe('Pam Beesly');
    expect($salons['Stamford']['isEmpty'])->toBeTrue();
    expect($salons['Nashua']['isOff'])->toBeTrue();
});

it('says where it cannot be booked among what stands out', function () {
    $michael = owner();
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $balayage = Service::factory()->create(['company_id' => $michael->company_id]);
    LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id]);

    $titles = array_column((new ServiceViewModel(user: $michael, service: $balayage))->insights(), 'title');

    expect($titles)->toContain('Cannot be booked at Stamford');
});

it('lists the facts of the service', function () {
    $michael = owner();
    $balayage = Service::factory()->create(['company_id' => $michael->company_id, 'prices' => ['CAD' => 18000]]);

    $facts = collect((new ServiceViewModel(user: $michael, service: $balayage))->facts())->pluck('value', 'label');

    expect($facts['Price'])->toBe('CA$ 180 · US$, no price yet');
    expect($facts['Offered at'])->toBe('0 salons');
    expect($facts['Done by'])->toBe('0 people');
});

it('offers the edit page only to somebody who may change services', function () {
    $michael = owner();
    $balayage = Service::factory()->create(['company_id' => $michael->company_id]);
    $dwight = test()->grant(User::factory()->create(['company_id' => $michael->company_id]), PermissionEnum::ServiceView);

    expect((new ServiceViewModel(user: $michael, service: $balayage))->editUrl())->toBe(route('services.edit', $balayage->id));
    expect((new ServiceViewModel(user: $dwight, service: $balayage))->editUrl())->toBeNull();
});

it('describes an archived service and how to bring it back', function () {
    $michael = owner();
    $perm = Service::factory()->create(['company_id' => $michael->company_id, 'is_active' => false]);

    $viewModel = new ServiceViewModel(user: $michael, service: $perm);

    expect($viewModel->isArchived())->toBeTrue();
    expect($viewModel->restoreUrl())->toBe(route('serviceArchives.destroy', $perm->id));
});

it('falls back to a sentence when the service has no description', function () {
    $michael = owner();
    $balayage = Service::factory()->create(['company_id' => $michael->company_id, 'description' => null]);

    expect((new ServiceViewModel(user: $michael, service: $balayage))->description())->toBe('');
});
