<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Services\CategoryServiceControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('opens the page to add a service with its category picked', function () {
    $michael = owner();
    $haircuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Haircuts']]);

    $response = $this->actingAs($michael)->get(route('categoryServices.new', $haircuts->id));

    $response->assertStatus(200);
    $response->assertSee('<option value="'.$haircuts->id.'" selected', false);
});

it('returns not found for a category of another company', function () {
    $michael = owner();
    $elsewhere = ServiceCategory::factory()->create();

    $this->actingAs($michael)->get(route('categoryServices.new', $elsewhere->id))->assertNotFound();
});

it('refuses somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);
    $haircuts = ServiceCategory::factory()->create(['company_id' => $company->id]);

    $this->actingAs($dwight)->get(route('categoryServices.new', $haircuts->id))->assertNotFound();
});
