<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration\LocationsViewModelTest;

use App\Enums\LocationScopeEnum;
use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Settings\Administration\LocationsViewModel;

it('counts what the company keeps', function () {
    $user = company();

    Location::factory()->primary()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/New_York']);
    Location::factory()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/Chicago']);
    Location::factory()->archived()->create(['company_id' => $user->company_id, 'country' => 'FR', 'timezone' => 'Europe/Paris']);

    $stats = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->stats();

    expect($stats)->toHaveCount(3);
    expect($stats[0]['value'])->toEqual(2);
    expect($stats[1]['value'])->toEqual(1);
    expect($stats[2]['value'])->toEqual(2);
});

it('lists the open locations by default', function () {
    $user = company();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

    $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();

    expect($rows)->toHaveCount(1);
    expect($rows[0]['name'])->toEqual('Scranton branch');
});

it('lists the archived locations on their own', function () {
    $user = company();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

    $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Archived)->rows();

    expect($rows)->toHaveCount(1);
    expect($rows[0]['name'])->toEqual('Nashua branch');
    expect($rows[0]['isArchived'])->toBeTrue();
});

it('lists every location when asked for all of them', function () {
    $user = company();

    Location::factory()->create(['company_id' => $user->company_id]);
    Location::factory()->archived()->create(['company_id' => $user->company_id]);

    expect(new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows())->toHaveCount(2);
});

it('badges the headquarters and the archived ones', function () {
    $user = company();

    Location::factory()->primary()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Utica branch']);
    Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Stamford branch']);

    $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows();

    expect($rows[0]['badge'])->toEqual('Headquarters');
    expect($rows[1]['badge'])->toEqual('Archived');
    expect($rows[2]['badge'])->toEqual('');
});

it('keeps the locations of another company out', function () {
    $user = company();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->create(['name' => 'Stamford branch']);

    $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows();

    expect($rows)->toHaveCount(1);
    expect($rows[0]['name'])->toEqual('Scranton branch');
});

it('searches the name the city and the country', function () {
    $user = company();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Scranton', 'country' => 'US']);
    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Paris location', 'city' => 'Paris', 'country' => 'FR']);

    $byName = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'scran')->rows();
    $byCity = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'paris')->rows();
    $byCountry = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'FR')->rows();

    expect($byName)->toHaveCount(1);
    expect($byName[0]['name'])->toEqual('Scranton branch');
    expect($byCity)->toHaveCount(1);
    expect($byCountry)->toHaveCount(1);
    expect($byCountry[0]['name'])->toEqual('Paris location');
});

it('orders the locations by location or by city', function () {
    $user = company();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Akron', 'country' => 'US']);
    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch', 'city' => 'Utica', 'country' => 'US']);

    $byName = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();
    $byPlace = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, sort: 'place')->rows();

    expect($byName[0]['name'])->toEqual('Nashua branch');
    expect($byPlace[0]['name'])->toEqual('Scranton branch');
});

it('says which order the list is in and leads to the other one', function () {
    $user = company();

    $byName = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->sortToggle();
    $byPlace = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, sort: 'place')->sortToggle();

    expect($byName['label'])->toEqual('Sorted by location');
    expect($byName['url'])->toEqual(route('settings.locations.index', ['sort' => 'place']));
    expect($byPlace['label'])->toEqual('Sorted by city');
    expect($byPlace['url'])->toEqual(route('settings.locations.index'));
});

it('falls back when an location has no country and no time zone', function () {
    $user = company();

    Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Somewhere',
        'address_line_1' => null,
        'country' => null,
        'city' => null,
        'timezone' => null,
    ]);

    $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();

    expect($rows[0]['place'])->toEqual('somewhere unrecorded');
    expect($rows[0]['timezone'])->toEqual('same as the company');
});

it('hands the panel everything it needs to edit an location', function () {
    $user = company();
    $location = Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Scranton branch',
        'region' => 'Pennsylvania',
        'postal_code' => '18505',
        'address_line_1' => '1725 Slough Avenue',
        'address_line_2' => 'Suite 200',
        'phone' => '570-555-0100',
        'email' => 'scranton@dundermifflin.com',
    ]);

    $drawer = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->drawer();

    expect($drawer)->toHaveKey($location->id);
    expect($drawer[$location->id]['name'])->toEqual('Scranton branch');
    expect($drawer[$location->id]['region'])->toEqual('Pennsylvania');
    expect($drawer[$location->id]['postalCode'])->toEqual('18505');
    expect($drawer[$location->id]['addressLine1'])->toEqual('1725 Slough Avenue');
    expect($drawer[$location->id]['addressLine2'])->toEqual('Suite 200');
    expect($drawer[$location->id]['phone'])->toEqual('570-555-0100');
    expect($drawer[$location->id]['email'])->toEqual('scranton@dundermifflin.com');
    expect($drawer[$location->id]['updateUrl'])->toEqual(route('settings.locations.update', $location->id));
    expect($drawer[$location->id]['archiveUrl'])->toEqual(route('settings.locationArchives.create', $location->id));
    expect($drawer[$location->id]['restoreUrl'])->toEqual(route('settings.locationArchives.destroy', $location->id));
});

it('says which of the three lists is being read', function () {
    $user = company();

    $scopes = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Archived)->scopes();

    expect($scopes)->toHaveCount(3);
    expect($scopes[0]['current'])->toBeFalse();
    expect($scopes[1]['current'])->toBeTrue();
    expect($scopes[1]['url'])->toEqual(route('settings.locations.index', 'archived'));
});

it('knows the company has no location at all', function () {
    $user = company();

    expect(new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->companyHasNoLocation())->toBeTrue();

    Location::factory()->create(['company_id' => $user->company_id]);

    expect(new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->companyHasNoLocation())->toBeFalse();
});

function company(): User
{
    $company = Company::factory()->create();

    return test()->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
}

it('lists only the locations a manager is assigned to', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);
    Location::factory()->create(['company_id' => $company->id, 'name' => 'Stamford branch']);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);

    $viewModel = new LocationsViewModel(user: $michael, scope: LocationScopeEnum::Active);

    expect(array_column($viewModel->rows(), 'name'))->toEqual(['Scranton branch']);
    expect($viewModel->canAddLocations())->toBeFalse();
});

it('lets somebody who manages every location add one', function () {
    $viewModel = new LocationsViewModel(user: company(), scope: LocationScopeEnum::Active);

    expect($viewModel->canAddLocations())->toBeTrue();
});

it('hands the panel who works at each location and who could', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $michaelStaff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
    $dwightAccount = User::factory()->create(['company_id' => $company->id]);
    $dwight = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $dwightAccount->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $dwight->locations()->attach($scranton);

    $drawer = new LocationsViewModel(user: $michael, scope: LocationScopeEnum::Active)->drawer();

    expect($drawer[$scranton->id]['people'])->toEqual([[
        'id' => $dwight->id,
        'name' => 'Dwight Schrute',
        'removeUrl' => route('settings.locationPeople.destroy', [$scranton->id, $dwight->id]),
    ]]);
    expect($drawer[$scranton->id]['assignable'])->toEqual([['id' => $michaelStaff->id, 'name' => 'Michael Scott']]);
    expect($drawer[$scranton->id]['assignUrl'])->toEqual(route('settings.locationPeople.create', $scranton->id));
});

it('keeps the people of a location from somebody who may not hand out roles', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);

    $viewModel = new LocationsViewModel(user: $michael, scope: LocationScopeEnum::Active);

    expect($viewModel->canAssignPeople())->toBeFalse();
    expect($viewModel->drawer()[$scranton->id]['people'])->toBe([]);
    expect($viewModel->drawer()[$scranton->id]['assignable'])->toBe([]);
});

it('hands the panel staff members without an account', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $creed = Staff::factory()->create(['company_id' => $company->id, 'user_id' => null, 'first_name' => 'Creed', 'last_name' => 'Bratton']);
    $meredith = Staff::factory()->create(['company_id' => $company->id, 'user_id' => null, 'first_name' => 'Meredith', 'last_name' => 'Palmer']);
    $meredith->locations()->attach($scranton);

    $drawer = new LocationsViewModel(user: $michael, scope: LocationScopeEnum::Active)->drawer();

    expect($drawer[$scranton->id]['people'])->toEqual([[
        'id' => $meredith->id,
        'name' => 'Meredith Palmer',
        'removeUrl' => route('settings.locationPeople.destroy', [$scranton->id, $meredith->id]),
    ]]);
    expect($drawer[$scranton->id]['assignable'])->toEqual([['id' => $creed->id, 'name' => 'Creed Bratton']]);
});
