<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;

it('opens and closes the menu', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $this->actingAs($michael);

    $page = visit(route('dashboard.index'));

    $page->click('button:has-text("Dunder Mifflin")')
        ->assertVisible('#top-bar-menu')
        ->keys('#top-bar-menu input', 'Escape')
        ->assertMissing('#top-bar-menu')
        ->keys('#main', 'g')
        ->assertVisible('#top-bar-menu')
        ->keys('#top-bar-menu input', 'g')
        ->assertVisible('#top-bar-menu')
        ->click('#top-bar-menu a:has-text("Team")')
        ->assertPathIs('/settings/administration/team')
        ->assertNoJavaScriptErrors();
});

it('stays where it is when a part has no screen yet', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $this->actingAs($michael);

    $page = visit(route('dashboard.index'));

    $page->click('button:has-text("Dunder Mifflin")');
    $page->script('document.querySelector(\'#top-bar-menu button[aria-disabled="true"]\').click()');

    $page->assertPathIs('/dashboard')
        ->assertVisible('#top-bar-menu')
        ->assertNoJavaScriptErrors();
});
