<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Services\ServiceArchiveControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en'], 'currencies' => ['CAD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('archives a service', function () {
    Queue::fake();

    $michael = owner();
    $perm = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Perm']]);

    $response = $this->actingAs($michael)->post(route('serviceArchives.create', $perm->id));

    $response->assertRedirect(route('services.index'));
    $response->assertSessionHas('status', 'The service is archived.');
    expect($perm->refresh()->is_active)->toBeFalse();
});

it('restores an archived service where it was offered', function () {
    Queue::fake();

    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);
    $perm = Service::factory()->create(['company_id' => $michael->company_id, 'is_active' => false]);
    LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $perm->id, 'is_active' => false]);

    $response = $this->actingAs($michael)->delete(route('serviceArchives.destroy', $perm->id));

    $response->assertRedirect(route('services.show', $perm->id));
    $response->assertSessionHas('status', 'The service is back in the catalog.');
    expect($perm->refresh()->is_active)->toBeTrue();
});

it('offers to restore an archived service from its page', function () {
    $michael = owner();
    $perm = Service::factory()->create(['company_id' => $michael->company_id, 'is_active' => false]);

    $response = $this->actingAs($michael)->get(route('services.show', $perm->id));

    $response->assertSee('Restore this service');
});

it('returns not found for a service of another company', function () {
    $michael = owner();
    $elsewhere = Service::factory()->create();

    $this->actingAs($michael)->post(route('serviceArchives.create', $elsewhere->id))->assertNotFound();
    $this->actingAs($michael)->delete(route('serviceArchives.destroy', $elsewhere->id))->assertNotFound();
});

it('refuses somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);
    $perm = Service::factory()->create(['company_id' => $company->id]);

    $this->actingAs($dwight)->post(route('serviceArchives.create', $perm->id))->assertNotFound();
    expect($perm->refresh()->is_active)->toBeTrue();
});
