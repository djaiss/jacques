<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Clients\ClientControllerTest;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('lists the clients of the company', function () {
    $michael = owner();
    Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
    Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);
    Client::factory()->create(['first_name' => 'Bob', 'last_name' => 'Vance']);

    $response = $this->actingAs($michael)->get(route('clients.index'));

    $response->assertStatus(200)
        ->assertSee('Pam Beesly')
        ->assertSee('Kevin Malone')
        ->assertDontSee('Bob Vance');
});

it('searches and narrows the list to one salon', function () {
    $michael = owner();
    $nashua = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Nashua']);
    $kevin = Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);
    Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
    Appointment::factory()->create(['company_id' => $michael->company_id, 'client_id' => $kevin->id, 'location_id' => $nashua->id]);

    $response = $this->actingAs($michael)->get(route('clients.index', ['q' => ' malone ', 'location' => $nashua->id]));

    $response->assertStatus(200)
        ->assertSee('Kevin Malone')
        ->assertDontSee('Pam Beesly')
        ->assertSee('1 client at Nashua.');
});

it('refuses a salon that is archived or of another company', function () {
    $michael = owner();
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'archived_at' => now()]);
    $vance = Location::factory()->create();

    $this->actingAs($michael)->get(route('clients.index', ['location' => $stamford->id]))->assertNotFound();
    $this->actingAs($michael)->get(route('clients.index', ['location' => $vance->id]))->assertNotFound();
});

it('refuses a page past the last one', function () {
    $michael = owner();
    Client::factory()->count(51)->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->get(route('clients.index', ['page' => 2]))->assertStatus(200)->assertSee('Page 2 of 2');
    $this->actingAs($michael)->get(route('clients.index', ['page' => 3]))->assertNotFound();
});

it('answers not found to somebody who may not see clients', function () {
    $company = Company::factory()->create();
    $toby = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($toby)->get(route('clients.index'))->assertNotFound();
});
