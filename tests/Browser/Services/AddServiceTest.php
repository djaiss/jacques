<?php

declare(strict_types=1);

namespace Tests\Browser\Services\AddServiceTest;

use App\Models\Company;
use App\Models\Location;
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

it('adds a service from its category, done by one person at one salon', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $haircuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Haircuts']]);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Jim Halpert', 'is_active' => true]);
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Pam Beesly', 'is_active' => true]);
    $jim->locations()->attach($scranton->id);
    $pam->locations()->attach($scranton->id);
    $this->actingAs($michael);

    $page = visit(route('services.index'));

    $page->click('a:has-text("Add a service in Haircuts")')
        ->assertPathIs('/service-categories/'.$haircuts->id.'/services/new')
        ->waitForEvent('networkidle')
        ->type('#service-name-en', 'Beard Trim')
        ->type('#service-price-CAD', '15')
        ->type('#service-price-USD', '12')
        ->clear('#service-duration')
        ->type('#service-duration', '20')
        ->check('fieldset:has-text("Scranton") > label input[type="checkbox"]')
        ->click('button:has-text("Tick everyone at Scranton")')
        ->uncheck('input[name="offers['.$scranton->id.'][]"][value="'.$pam->id.'"]')
        ->click('button:has-text("Add this service")')
        ->assertSee('The service is added.')
        ->assertSee('Beard Trim')
        ->assertSee('Jim Halpert')
        ->assertDontSee('Pam Beesly')
        ->assertNoJavaScriptErrors();

    expect(Service::query()->where('company_id', $michael->company_id)->sole()->service_category_id)->toBe($haircuts->id);
});

it('shows why a service without a name cannot be added', function () {
    $michael = owner();
    $this->actingAs($michael);

    $page = visit(route('services.index'));

    $page->click('main a:has-text("Add a service")')
        ->assertPathIs('/services/new')
        ->click('button:has-text("Add this service")')
        ->assertPathIs('/services/new')
        ->assertSee('The service needs a name.')
        ->assertNoJavaScriptErrors();
});
