<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;

it('starts an empty catalogue from the ready-made one', function () {
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $this->actingAs($michael);

    $page = visit(route('services.index'));

    $page->assertSee('Nothing for clients to book yet.')
        ->assertSee('No services yet')
        ->click('button:has-text("Start from a ready-made catalog")')
        ->assertPathIs('/services')
        ->assertSee('The ready-made catalog is added.')
        ->assertDontSee('No services yet')
        ->assertNoJavaScriptErrors();
});

it('adds the first service from the empty catalogue', function () {
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $this->actingAs($michael);

    $page = visit(route('services.index'));

    $page->click('main a:has-text("Add a service")')
        ->assertPathIs('/services/new')
        ->assertNoJavaScriptErrors();
});
