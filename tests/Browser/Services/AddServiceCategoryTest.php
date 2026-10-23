<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;

it('adds an empty category to the catalogue', function () {
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $this->actingAs($michael);

    $page = visit(route('services.index'));

    $page->click('a:has-text("Add a category")')
        ->assertPathIs('/service-categories/new')
        ->type('#category-name-en', 'Barbering')
        ->click('button:has-text("Add this category")')
        ->assertPathIs('/services')
        ->assertSee('The category is added.')
        ->assertSee('Barbering')
        ->assertSee('No services in this category yet.')
        ->assertNoJavaScriptErrors();
});
