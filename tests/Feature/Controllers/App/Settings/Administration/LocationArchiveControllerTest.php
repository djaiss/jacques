<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\LocationArchiveControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('archives an location', function () {
    Queue::fake();

    $user = administrator();
    $location = Location::factory()->create(['company_id' => $user->company_id]);

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->post(route('settings.locationArchives.create', $location->id));

    $response->assertRedirect(route('settings.locations.index'));
    $response->assertSessionHas('status', 'The location is archived.');

    expect($location->refresh()->isArchived())->toBeTrue();
});

it('reopens an location', function () {
    Queue::fake();

    $user = administrator();
    $location = Location::factory()->archived()->create(['company_id' => $user->company_id]);

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index', 'archived'))
        ->delete(route('settings.locationArchives.destroy', $location->id));

    $response->assertRedirect(route('settings.locations.index', 'archived'));
    $response->assertSessionHas('status', 'The location is open again.');

    expect($location->refresh()->isArchived())->toBeFalse();
});

it('cannot archive an location of another company', function () {
    $user = administrator();
    $location = Location::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.locationArchives.create', $location->id))
        ->assertNotFound();
});

it('stops somebody who may not administer the company from archiving an location', function () {
    $company = Company::factory()->create();
    $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::STAFF);
    $location = Location::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)
        ->post(route('settings.locationArchives.create', $location->id))
        ->assertNotFound();

    expect($location->refresh()->isArchived())->toBeFalse();
});

it('goes back to the list it was asked from', function () {
    Queue::fake();

    $user = administrator();
    $location = Location::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->from(route('settings.locations.index', 'all'))
        ->post(route('settings.locationArchives.create', $location->id))
        ->assertRedirect(route('settings.locations.index', 'all'));
});

function administrator(): User
{
    $company = Company::factory()->create();

    return test()->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
}
