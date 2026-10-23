<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Clients\ClientNotRebookedControllerTest;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('lists the clients with nothing coming up', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = owner();
    $pam = Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
    Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);
    Appointment::factory()->create(['company_id' => $michael->company_id, 'client_id' => $pam->id, 'starts_at' => '2026-04-06 15:00:00', 'ends_at' => '2026-04-06 16:00:00']);

    $response = $this->actingAs($michael)->get(route('clientsNotRebooked.index'));

    $response->assertStatus(200)
        ->assertSee('Kevin Malone')
        ->assertDontSee('Pam Beesly');
});

it('answers not found to somebody who may not see clients', function () {
    $toby = User::factory()->create(['company_id' => Company::factory()->create()->id]);

    $this->actingAs($toby)->get(route('clientsNotRebooked.index'))->assertNotFound();
});
