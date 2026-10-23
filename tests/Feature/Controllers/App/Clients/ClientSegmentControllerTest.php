<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Clients\ClientSegmentControllerTest;

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

it('lists the clients of a segment', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = owner();
    $kevin = Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);
    Client::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
    Appointment::factory()->create(['company_id' => $michael->company_id, 'client_id' => $kevin->id, 'starts_at' => '2025-09-01 15:00:00', 'ends_at' => '2025-09-01 16:00:00']);

    $response = $this->actingAs($michael)->get(route('clientSegments.show', ['segment' => 'inactive']));

    $response->assertStatus(200)
        ->assertSee('Kevin Malone')
        ->assertDontSee('Pam Beesly');
});

it('answers not found to a segment that does not exist', function () {
    $this->actingAs(owner())->get('/clients/segments/vip')->assertNotFound();
});

it('answers not found to somebody who may not see clients', function () {
    $toby = User::factory()->create(['company_id' => Company::factory()->create()->id]);

    $this->actingAs($toby)->get(route('clientSegments.show', ['segment' => 'new']))->assertNotFound();
});
