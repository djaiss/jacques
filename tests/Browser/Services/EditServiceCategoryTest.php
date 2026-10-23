<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

it('renames a category, then removes it and keeps its service', function () {
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $makeup = ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Makeup']]);
    Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $makeup->id, 'name' => ['en' => 'Bridal Makeup']]);
    $this->actingAs($michael);

    $page = visit(route('services.index'));

    $page->click('a[aria-label="Edit the category Makeup"]')
        ->assertPathIs('/service-categories/'.$makeup->id.'/edit')
        ->type('#category-name-en', 'Make-up')
        ->click('button:has-text("Save changes")')
        ->assertSee('The category is saved.')
        ->assertSee('Make-up')
        ->click('a[aria-label="Edit the category Make-up"]')
        ->click('button:has-text("Remove this category")')
        ->assertSee('Remove Make-up?')
        ->click('[role="dialog"] button:has-text("Remove the category")')
        ->assertPathIs('/services')
        ->assertSee('The category is removed.')
        ->assertSee('Without a category')
        ->assertSee('Bridal Makeup')
        ->assertNoJavaScriptErrors();
});
