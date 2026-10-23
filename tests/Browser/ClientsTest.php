<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;

it('searches, picks a salon and switches tabs', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $nashua = Location::factory()->create(['company_id' => $company->id, 'name' => 'Nashua']);
    $kevin = Client::factory()->create(['company_id' => $company->id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);
    Client::factory()->create(['company_id' => $company->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
    Appointment::factory()->create(['company_id' => $company->id, 'client_id' => $kevin->id, 'location_id' => $nashua->id, 'starts_at' => now()->subMonths(7), 'ends_at' => now()->subMonths(7)]);
    $this->actingAs($michael);

    $page = visit(route('clients.index'));

    $page->assertSee('Pam Beesly')
        ->type('#clients-search', 'pam')
        ->keys('#clients-search', 'Enter')
        ->assertSee('Pam Beesly')
        ->assertDontSee('Kevin Malone')
        ->type('#clients-search', '')
        ->keys('#clients-search', 'Enter')
        ->select('#clients-salon', (string) $nashua->id)
        ->assertSee('1 client at Nashua.')
        ->click('a:has-text("Inactive clients")')
        ->assertSee('Kevin Malone')
        ->assertPathIs('/clients/segments/inactive')
        ->assertNoJavaScriptErrors();
});

it('fits a phone without scrolling sideways', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Client::factory()->create(['company_id' => $company->id, 'first_name' => 'Creed', 'last_name' => 'Bratton']);
    $this->actingAs($michael);

    $page = visit(route('clients.index'))->on()->mobile();

    $page->assertSee('Creed Bratton')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors();
});
