<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration\LocationPeopleControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('assigns somebody to a location', function () {
    Queue::fake();

    $user = administrator();
    $scranton = Location::factory()->create(['company_id' => $user->company_id]);
    $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $dwight->id]);

    $response->assertRedirect(route('settings.locations.index'));
    $response->assertSessionHas('status', 'The person is assigned to the location.');
    $response->assertSessionHas('status_description', 'Dwight Schrute works there from now on.');
    $this->assertDatabaseHas('location_staff', ['staff_id' => $dwight->id, 'location_id' => $scranton->id]);
});

it('assigns a staff member without an account', function () {
    Queue::fake();

    $user = administrator();
    $scranton = Location::factory()->create(['company_id' => $user->company_id]);
    $creed = Staff::factory()->create(['company_id' => $user->company_id, 'user_id' => null]);

    $this->actingAs($user)
        ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $creed->id]);

    $this->assertDatabaseHas('location_staff', ['staff_id' => $creed->id, 'location_id' => $scranton->id]);
});

it('does not find a staff member of another company', function () {
    $user = administrator();
    $scranton = Location::factory()->create(['company_id' => $user->company_id]);
    $karen = Staff::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $karen->id])
        ->assertNotFound();

    $this->assertDatabaseMissing('location_staff', ['staff_id' => $karen->id]);
});

it('takes somebody off a location', function () {
    Queue::fake();

    $user = administrator();
    $scranton = Location::factory()->create(['company_id' => $user->company_id]);
    $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
    $dwight->locations()->attach($scranton);

    $response = $this->actingAs($user)
        ->from(route('settings.locations.index'))
        ->delete(route('settings.locationPeople.destroy', [$scranton->id, $dwight->id]));

    $response->assertRedirect(route('settings.locations.index'));
    $response->assertSessionHas('status', 'The person is taken off the location.');
    $response->assertSessionHas('status_description', 'Dwight Schrute keeps every other location they work at.');
    $this->assertDatabaseMissing('location_staff', ['staff_id' => $dwight->id, 'location_id' => $scranton->id]);
});

it('does not take off a staff member of another company', function () {
    $user = administrator();
    $scranton = Location::factory()->create(['company_id' => $user->company_id]);
    $karen = Staff::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.locationPeople.destroy', [$scranton->id, $karen->id]))
        ->assertNotFound();
});

it('turns away a request without anybody to assign', function () {
    $user = administrator();
    $scranton = Location::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->post(route('settings.locationPeople.create', $scranton->id), [])
        ->assertSessionHasErrors('staff');
});

it('does not find a location of another company', function () {
    $user = administrator();
    $nashua = Location::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->post(route('settings.locationPeople.create', $nashua->id), ['staff' => $dwight->id])
        ->assertNotFound();
});

it('refuses a manager', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);
    $dwight = Staff::factory()->create(['company_id' => $company->id]);

    $this->actingAs($michael)
        ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $dwight->id])
        ->assertNotFound();
});

function administrator(): User
{
    $company = Company::factory()->create();

    return test()->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
}
