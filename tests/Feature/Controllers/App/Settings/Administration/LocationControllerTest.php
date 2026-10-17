<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\LocationControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows the locations of the company', function () {
    $user = administrator();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);

    $response = $this->actingAs($user)->get(route('settings.locations.index'));

    $response->assertOk();
    $response->assertSee('Locations');
    $response->assertSee('Add a location');
    $response->assertSee('open locations');
    $response->assertSee('Sorted by location');
    $response->assertSee('Scranton branch');
});

it('orders the locations by city when the path asks for it', function () {
    $user = administrator();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Akron']);
    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch', 'city' => 'Utica']);

    $response = $this->actingAs($user)->get(route('settings.locations.index', ['sort' => 'place']));

    $response->assertOk();
    $response->assertSee('Sorted by city');
    $response->assertSeeInOrder(['Scranton branch', 'Nashua branch']);
});

it('keeps the archived locations off the first list', function () {
    $user = administrator();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

    $response = $this->actingAs($user)->get(route('settings.locations.index'));

    $response->assertOk();
    $response->assertSee('Scranton branch');
    $response->assertDontSee('Nashua branch');
});

it('shows the archived locations on a path of their own', function () {
    $user = administrator();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

    $response = $this->actingAs($user)->get(route('settings.locations.index', 'archived'));

    $response->assertOk();
    $response->assertSee('Nashua branch');
    $response->assertDontSee('Scranton branch');
});

it('shows every location on the third path', function () {
    $user = administrator();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

    $response = $this->actingAs($user)->get(route('settings.locations.index', 'all'));

    $response->assertOk();
    $response->assertSee('Scranton branch');
    $response->assertSee('Nashua branch');
});

it('narrows the list down to what was searched for', function () {
    $user = administrator();

    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
    Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Utica branch']);

    $response = $this->actingAs($user)->get(route('settings.locations.index', ['q' => 'utica']));

    $response->assertOk();
    $response->assertSee('Utica branch');
    $response->assertDontSee('Scranton branch');
});

it('turns away a scope it does not know', function () {
    $user = administrator();

    $this->actingAs($user)->get('settings/administration/locations/somewhere')->assertNotFound();
});

it('hides the screen from somebody who may not administer the company', function () {
    $user = member();

    $this->actingAs($user)->get(route('settings.locations.index'))->assertNotFound();
});

it('creates an location', function () {
    Queue::fake();

    $user = administrator();

    $response = $this->actingAs($user)->post(route('settings.locations.create'), [
        'name' => 'Scranton branch',
        'city' => 'Scranton',
        'country' => 'us',
    ]);

    $response->assertRedirect(route('settings.locations.index'));
    $response->assertSessionHas('status', 'The location is added.');

    $this->assertDatabaseHas('locations', [
        'company_id' => $user->company_id,
        'name' => 'Scranton branch',
        'city' => 'Scranton',
        'country' => 'US',
    ]);
});

it('sends the new location messages to a bag of their own', function () {
    $user = administrator();

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->post(route('settings.locations.create'), ['name' => '']);

    $response->assertRedirect(route('settings.locations.index'));
    $response->assertSessionHasErrorsIn('createLocation', ['name']);
});

it('turns away a country that is not two letters', function () {
    $user = administrator();

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->post(route('settings.locations.create'), ['name' => 'Scranton branch', 'country' => 'USA']);

    $response->assertSessionHasErrorsIn('createLocation', ['country']);
});

it('saves an location', function () {
    Queue::fake();

    $user = administrator();
    $location = Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->put(route('settings.locations.update', $location->id), [
            'name' => 'Scranton business park',
            'city' => 'Scranton',
            'country' => 'US',
            'region' => 'Pennsylvania',
            'postal_code' => '18505',
            'address_line_1' => '1725 Slough Avenue',
            'address_line_2' => 'Suite 200',
            'phone' => '570-555-0100',
            'email' => 'scranton@dundermifflin.com',
            'timezone' => 'America/New_York',
            'is_headquarters' => '1',
        ]);

    $response->assertRedirect(route('settings.locations.index'));
    $response->assertSessionHas('status', 'The location is saved.');

    $location->refresh();

    expect($location->name)->toEqual('Scranton business park');
    expect($location->address_line_1)->toEqual('1725 Slough Avenue');
    expect($location->address_line_2)->toEqual('Suite 200');
    expect($location->email)->toEqual('scranton@dundermifflin.com');
    expect($location->timezone)->toEqual('America/New_York');
    expect($location->is_headquarters)->toBeTrue();
});

it('goes back to the list the save was made from', function () {
    Queue::fake();

    $user = administrator();
    $location = Location::factory()->archived()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->from(route('settings.locations.index', 'archived'))
        ->put(route('settings.locations.update', $location->id), ['name' => 'Nashua branch'])
        ->assertRedirect(route('settings.locations.index', 'archived'));
});

it('turns away a time zone that does not exist', function () {
    $user = administrator();
    $location = Location::factory()->create(['company_id' => $user->company_id]);

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->put(route('settings.locations.update', $location->id), [
            'name' => 'Scranton branch',
            'timezone' => 'Middle/Earth',
        ]);

    $response->assertSessionHasErrors(['timezone']);
});

it('turns away an email that is not an address', function () {
    $user = administrator();
    $location = Location::factory()->create(['company_id' => $user->company_id]);

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->put(route('settings.locations.update', $location->id), [
            'name' => 'Scranton branch',
            'email' => 'dwight at schrute farms',
        ]);

    $response->assertSessionHasErrors(['email']);
});

it('cannot save an location of another company', function () {
    $user = administrator();
    $location = Location::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.locations.update', $location->id), ['name' => 'Stamford branch'])
        ->assertNotFound();
});

it('stops somebody who may not administer the company from adding an location', function () {
    $user = member();

    $this->actingAs($user)
        ->post(route('settings.locations.create'), ['name' => 'Scranton branch'])
        ->assertNotFound();
});

it('shows a manager only their locations and no way to add one', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);
    Location::factory()->create(['company_id' => $company->id, 'name' => 'Stamford branch']);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);

    $response = $this->actingAs($michael)->get(route('settings.locations.index'));

    $response->assertOk();
    $response->assertSee('Scranton branch');
    $response->assertDontSee('Stamford branch');
    $response->assertDontSee('Add a location');
    $response->assertDontSee('People who work here');
});

it('does not find a location a manager is not assigned to', function () {
    $company = Company::factory()->create();
    $stamford = Location::factory()->create(['company_id' => $company->id]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    $this->actingAs($michael)
        ->put(route('settings.locations.update', $stamford->id), ['name' => 'Stamford branch'])
        ->assertNotFound();
});

it('offers the owner a way to assign people to a location', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $response = $this->actingAs($michael)->get(route('settings.locations.index'));

    $response->assertSee('People who work here');
    $response->assertSee('Take this person off the location?');
});

function administrator(): User
{
    $company = Company::factory()->create();

    return test()->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
}

function member(): User
{
    $company = Company::factory()->create();

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::STAFF);
}

it('gives the fields of the panel and of the dialog different ids', function () {
    $user = administrator();

    Location::factory()->create(['company_id' => $user->company_id]);

    $response = $this->actingAs($user)->get(route('settings.locations.index'));

    expect(substr_count($response->getContent(), 'id="name"'))->toBe(1);
    expect(substr_count($response->getContent(), 'id="location_name"'))->toBe(1);
    $response->assertSee('name="name"', escape: false);
});
