<?php

declare(strict_types=1);

namespace Tests\Browser\Services\ServicesTest;

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\User;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin', 'languages' => ['en'], 'currencies' => ['CAD', 'USD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('reaches the catalogue from the menu, searches it and picks a salon', function () {
    $michael = owner();
    Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $colors = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Hair Coloring']]);
    $balayage = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colors->id, 'name' => ['en' => 'Balayage'], 'position' => 1]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colors->id, 'name' => ['en' => 'Highlights'], 'position' => 2]);
    $karen = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Karen Filippelli', 'is_active' => true]);
    $karen->locations()->attach($stamford->id);
    LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id])->staff()->attach($karen->id);
    $this->actingAs($michael);

    $page = visit(route('dashboard.index'));

    $page->click('button:has-text("Dunder Mifflin")')
        ->click('#top-bar-menu a:has-text("Services")')
        ->assertPathIs('/services')
        ->assertSee('2 services in 1 category, offered across 2 salons.')
        ->type('#services-search', 'balay')
        ->keys('#services-search', 'Enter')
        ->assertSee('Balayage')
        ->assertDontSee('Highlights')
        ->type('#services-search', 'zzz')
        ->keys('#services-search', 'Enter')
        ->assertSee('No service is called that.')
        ->assertNoJavaScriptErrors();

    $page = visit(route('services.index'));

    $page->click('nav a:has-text("Stamford")')
        ->assertPathIs('/services/locations/'.$stamford->id)
        ->assertSee('1 of 2 services is offered at Stamford, by 1 person.')
        ->assertSee('Not offered at Stamford')
        ->click('a:has-text("Balayage")')
        ->assertPathIs('/services/'.$balayage->id)
        ->assertNoJavaScriptErrors();
});
