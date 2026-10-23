<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Services\ServiceCatalogueControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('fills an empty catalogue with the ready-made one', function () {
    Queue::fake();

    $michael = owner();

    $response = $this->actingAs($michael)->post(route('serviceCatalogues.create'));

    $response->assertRedirect(route('services.index'));
    $response->assertSessionHas('status', 'The ready-made catalog is added.');
    expect(Service::query()->where('company_id', $michael->company_id)->count())->toBeGreaterThan(0);
});

it('leaves a catalogue that holds a service alone', function () {
    Queue::fake();

    $michael = owner();
    Service::factory()->create(['company_id' => $michael->company_id, 'is_active' => false]);

    $response = $this->actingAs($michael)->post(route('serviceCatalogues.create'));

    $response->assertRedirect(route('services.index'));
    $response->assertSessionHas('status', 'The ready-made catalog only goes into an empty catalog.');
    expect(Service::query()->where('company_id', $michael->company_id)->count())->toBe(1);
});

it('refuses somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);

    $this->actingAs($dwight)->post(route('serviceCatalogues.create'))->assertNotFound();
    $this->assertDatabaseCount('services', 0);
});
