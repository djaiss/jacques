<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Services\ServiceLocationControllerTest;

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('lists the catalogue for one salon', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $updo = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Updo']]);
    Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage']]);
    $offer = LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $updo->id]);
    $andy = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Andy Bernard', 'is_active' => true]);
    $andy->locations()->attach($stamford->id);
    $offer->staff()->attach($andy->id);

    $response = $this->actingAs($michael)->get(route('serviceLocations.index', $stamford->id));

    $response->assertStatus(200);
    $response->assertSee('1 of 2 services is offered at Stamford, by 1 person.');
    $response->assertSee('People at Stamford');
    $response->assertSee('Not offered at Stamford');
    $response->assertSee('aria-current="page"', false);
    expect($scranton->exists)->toBeTrue();
});

it('keeps the search when it moves to another salon', function () {
    $michael = owner();
    $stamford = Location::factory()->create(['company_id' => $michael->company_id]);
    Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Haircut']]);

    $response = $this->actingAs($michael)->get(route('services.index', ['q' => 'cut']));

    $response->assertSee(route('serviceLocations.index', $stamford->id).'?q=cut', false);
});

it('returns not found for a salon of another company', function () {
    $michael = owner();
    $elsewhere = Location::factory()->create();

    $this->actingAs($michael)->get(route('serviceLocations.index', $elsewhere->id))->assertNotFound();
});

it('returns not found for an archived salon', function () {
    $michael = owner();
    $nashua = Location::factory()->create(['company_id' => $michael->company_id, 'archived_at' => now()]);

    $this->actingAs($michael)->get(route('serviceLocations.index', $nashua->id))->assertNotFound();
});

it('refuses somebody who may not see services', function () {
    $toby = User::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $toby->company_id]);

    $this->actingAs($toby)->get(route('serviceLocations.index', $scranton->id))->assertNotFound();
});
