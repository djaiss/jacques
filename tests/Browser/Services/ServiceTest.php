<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;

it('reads a service, its sample figures and who does it in each salon', function () {
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $company->id, 'name' => 'Stamford']);
    Location::factory()->create(['company_id' => $company->id, 'name' => 'Nashua']);
    $pam = Staff::factory()->create(['company_id' => $company->id, 'display_name' => 'Pam Beesly', 'is_active' => true]);
    $pam->locations()->attach($scranton->id);
    $balayage = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Balayage']]);
    LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $balayage->id])->staff()->attach($pam->id);
    LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id]);
    $this->actingAs($michael);

    $page = visit(route('services.show', $balayage->id));

    $page->assertSee('Sample figures. Real ones arrive with appointments.')
        ->assertSee('Bookings per week')
        ->assertSee('Pam Beesly')
        ->assertSee('Offered here, but nobody does it yet, so it cannot be booked.')
        ->assertSee('Not offered here.')
        ->click('a:has-text("Edit this service")')
        ->assertPathIs('/services/'.$balayage->id.'/edit')
        ->assertNoJavaScriptErrors();
});

it('reads a service without changing it when one may only look', function () {
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);
    $balayage = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Balayage']]);
    $this->actingAs($dwight);

    $page = visit(route('services.show', $balayage->id));

    $page->assertSee('Balayage')
        ->assertDontSee('Edit this service')
        ->assertNoJavaScriptErrors();
});
