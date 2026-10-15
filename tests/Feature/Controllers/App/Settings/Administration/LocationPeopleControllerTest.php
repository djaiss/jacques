<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationPeopleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_assigns_somebody_to_a_location(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $scranton = Location::factory()->create(['company_id' => $user->company_id]);
        $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index'))
            ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $dwight->id]);

        $response->assertRedirect(route('settings.locations.index'));
        $response->assertSessionHas('status', 'The person is assigned to the location.');
        $response->assertSessionHas('status_description', 'Dwight Schrute works there from now on.');
        $this->assertDatabaseHas('location_staff', ['staff_id' => $dwight->id, 'location_id' => $scranton->id]);
    }

    #[Test]
    public function it_assigns_a_staff_member_without_an_account(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $scranton = Location::factory()->create(['company_id' => $user->company_id]);
        $creed = Staff::factory()->create(['company_id' => $user->company_id, 'user_id' => null]);

        $this->actingAs($user)
            ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $creed->id]);

        $this->assertDatabaseHas('location_staff', ['staff_id' => $creed->id, 'location_id' => $scranton->id]);
    }

    #[Test]
    public function it_does_not_find_a_staff_member_of_another_company(): void
    {
        $user = $this->administrator();
        $scranton = Location::factory()->create(['company_id' => $user->company_id]);
        $karen = Staff::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $karen->id])
            ->assertNotFound();

        $this->assertDatabaseMissing('location_staff', ['staff_id' => $karen->id]);
    }

    #[Test]
    public function it_takes_somebody_off_a_location(): void
    {
        Queue::fake();

        $user = $this->administrator();
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
    }

    #[Test]
    public function it_does_not_take_off_a_staff_member_of_another_company(): void
    {
        $user = $this->administrator();
        $scranton = Location::factory()->create(['company_id' => $user->company_id]);
        $karen = Staff::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.locationPeople.destroy', [$scranton->id, $karen->id]))
            ->assertNotFound();
    }

    #[Test]
    public function it_turns_away_a_request_without_anybody_to_assign(): void
    {
        $user = $this->administrator();
        $scranton = Location::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('settings.locationPeople.create', $scranton->id), [])
            ->assertSessionHasErrors('staff');
    }

    #[Test]
    public function it_does_not_find_a_location_of_another_company(): void
    {
        $user = $this->administrator();
        $nashua = Location::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('settings.locationPeople.create', $nashua->id), ['staff' => $dwight->id])
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_manager(): void
    {
        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id])->locations()->attach($scranton);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);

        $this->actingAs($michael)
            ->post(route('settings.locationPeople.create', $scranton->id), ['staff' => $dwight->id])
            ->assertNotFound();
    }

    private function administrator(): User
    {
        $company = Company::factory()->create();

        return $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    }
}
