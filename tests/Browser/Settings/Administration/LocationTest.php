<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Staff;
use App\Models\User;

it('adds a location', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'));

    $page->click('button:has-text("Add a location")')
        ->assertVisible('#create-location-title')
        ->type('#name', 'Scranton branch')
        ->type('#city', 'Scranton')
        ->type('#country', 'US')
        ->click('[aria-labelledby="create-location-title"] button:has-text("Add the location")')
        ->assertSee('The location is added.')
        ->assertSee('Scranton branch')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('locations', [
        'company_id' => $user->company_id,
        'name' => 'Scranton branch',
        'city' => 'Scranton',
        'country' => 'US',
    ]);
});

it('shows an error when the name of a new location is too short', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'));

    $page->click('button:has-text("Add a location")')
        ->type('#name', 'A')
        ->click('[aria-labelledby="create-location-title"] button:has-text("Add the location")')
        ->assertSee('The name field must be at least 2 characters.')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseCount('locations', 0);
});

it('edits a location', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    $location = Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Stamford branch',
        'city' => 'Stamford',
        'country' => 'US',
    ]);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'));

    $page->click('#locations-table button:has-text("Stamford branch")')
        ->assertVisible('#location-panel-title')
        ->type('#location_name', 'Nashua branch')
        ->type('#location_city', 'Nashua')
        ->click('button[form="location-form"]')
        ->assertSee('Nashua branch')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('locations', [
        'id' => $location->id,
        'name' => 'Nashua branch',
        'city' => 'Nashua',
    ]);
});

it('adds a location on a phone', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'))->on()->mobile();

    $page->click('button:has-text("Add a location")')
        ->type('#name', 'Nashua branch')
        ->click('[aria-labelledby="create-location-title"] button:has-text("Add the location")')
        ->assertSee('The location is added.')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('locations', ['name' => 'Nashua branch']);
});

it('refuses a person who may not manage locations', function () {
    $this->actingAs(User::factory()->create());

    visit(route('settings.locations.index'))
        ->assertSee('Not Found')
        ->assertDontSee('Add a location');
});

it('archives a location after asking, and reopens it', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    $location = Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Scranton branch',
    ]);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'));

    $page->click('#locations-table button:has-text("Scranton branch")')
        ->click('button:has-text("Archive this location")')
        ->assertSee('Archive this location?')
        ->click('[aria-labelledby="archive-location-title"] button:has-text("Keep it open")')
        ->assertDontSee('Archive this location?')
        ->assertSeeIn('#locations-table', 'Scranton branch');

    expect($location->refresh()->archived_at)->toBeNull();

    $page->click('button:has-text("Archive this location")')
        ->click('[aria-labelledby="archive-location-title"] button:has-text("Archive it")')
        ->assertDontSeeIn('#locations-table', 'Scranton branch')
        ->assertNoJavaScriptErrors();

    expect($location->refresh()->archived_at)->not->toBeNull();

    $page = visit(route('settings.locations.index', ['scope' => 'archived']));

    $page->click('#locations-table button:has-text("Scranton branch")')
        ->click('button:has-text("Reopen this location")')
        ->assertDontSeeIn('#locations-table', 'Scranton branch')
        ->assertNoJavaScriptErrors();

    expect($location->refresh()->archived_at)->toBeNull();
});

it('searches the locations by name, city and country', function (string $search) {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Scranton branch',
        'city' => 'Scranton',
        'country' => 'US',
    ]);
    Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Utica branch',
        'city' => 'Utica',
        'country' => 'CA',
    ]);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'));

    $page->type('#q', $search)
        ->keys('#q', 'Enter')
        ->assertSeeIn('#locations-table', 'Scranton branch')
        ->assertDontSeeIn('#locations-table', 'Utica branch')
        ->assertNoJavaScriptErrors();
})->with([
    'name' => 'Scranton branch',
    'city' => 'Scranton',
    'country' => 'US',
]);

it('says so when no location matches the search', function () {
    $user = User::factory()->create();
    $this->grant($user, PermissionEnum::LocationManage);
    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'));

    $page->type('#q', 'Akron')
        ->keys('#q', 'Enter')
        ->assertSee('No location matches this search')
        ->assertDontSeeIn('#locations-table', 'Scranton branch')
        ->assertNoJavaScriptErrors();
});

it('assigns a colleague to a location and takes them off after asking', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::LocationManage);
    $this->grant($user, PermissionEnum::RoleManage);
    $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);
    $jim = Staff::factory()->create(['company_id' => $company->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
    Staff::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
    $this->actingAs($user);

    $page = visit(route('settings.locations.index'));

    $page->click('#locations-table button:has-text("Scranton branch")')
        ->assertDontSee('Michael Scott')
        ->select('#location-assignee', (string) $jim->id)
        ->click('button:has-text("Assign")')
        ->assertSeeIn('#location-people-title + p + ul li > span:first-child', 'Jim Halpert');

    expect($jim->locations()->where('locations.id', $location->id)->exists())->toBeTrue();

    $page->click('ul button:has-text("Remove")')
        ->assertSee('Take this person off the location?')
        ->click('[aria-labelledby="unassign-person-title"] button:has-text("Keep them")')
        ->assertDontSee('Take this person off the location?')
        ->assertSeeIn('#location-people-title + p + ul li > span:first-child', 'Jim Halpert');

    expect($jim->locations()->where('locations.id', $location->id)->exists())->toBeTrue();

    $page->click('ul button:has-text("Remove")')
        ->click('[aria-labelledby="unassign-person-title"] button:has-text("Take them off")')
        ->assertSee('Nobody is assigned to this location yet.')
        ->assertNoJavaScriptErrors();

    expect($jim->locations()->where('locations.id', $location->id)->exists())->toBeFalse();
});
