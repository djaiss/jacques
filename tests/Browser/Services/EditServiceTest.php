<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;

it('renames a service in French, archives it and restores it', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr'], 'currencies' => ['CAD']]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $trim = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Beard Trim']]);
    $this->actingAs($michael);

    $page = visit(route('services.edit', $trim->id));

    $page->click('button:has-text("Français")')
        ->type('#service-name-fr', 'Taille de barbe')
        ->click('button:has-text("Save changes")')
        ->assertPathIs('/services/'.$trim->id)
        ->assertSee('The service is saved.')
        ->assertNoJavaScriptErrors();

    expect($trim->refresh()->name)->toBe(['en' => 'Beard Trim', 'fr' => 'Taille de barbe']);

    $page = visit(route('services.edit', $trim->id));

    $page->click('button:has-text("Archive this service")')
        ->assertSee('The service leaves the catalog and nobody can book it. Sure?')
        ->click('button:has-text("Keep it")')
        ->assertDontSee('The service leaves the catalog and nobody can book it. Sure?')
        ->click('button:has-text("Archive this service")')
        ->click('button:has-text("Archive it")')
        ->assertPathIs('/services')
        ->assertSee('The service is archived.')
        ->assertDontSee('Beard Trim')
        ->assertNoJavaScriptErrors();

    $page = visit(route('services.show', $trim->id));

    $page->click('button:has-text("Restore this service")')
        ->assertSee('The service is back in the catalog.')
        ->assertNoJavaScriptErrors();

    expect($trim->refresh()->is_active)->toBeTrue();
});
