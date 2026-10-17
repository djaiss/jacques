<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Availability;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffHoursControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_panel_on_a_team_member(): void
    {
        [$michael, $scranton] = $this->owner();
        $jim = $this->member($scranton, 'Jim');

        $response = $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $jim));

        $response->assertStatus(200);
        $response->assertSee('Working hours');
        $response->assertSee('Save hours');
        $response->assertSee('Start again from salon hours');
        $response->assertSee(route('onboarding.availability.staff.update', $jim));
    }

    #[Test]
    public function it_stores_the_hours_of_a_team_member_as_their_own(): void
    {
        Queue::fake();

        [$michael, $scranton] = $this->owner();
        $jim = $this->member($scranton, 'Jim');

        $response = $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $jim), [
            'days' => [
                0 => ['open' => '1', 'ranges' => [['start' => '10:00', 'end' => '14:00']]],
                2 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '18:00']]],
                3 => ['open' => '0', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
            ],
        ]);

        $response->assertRedirect(route('onboarding.availability.index'));
        $this->assertEquals([0, 2, 2], $jim->workingHours()->orderBy('day_of_week')->pluck('day_of_week')->all());
        $this->assertFalse((bool) $jim->locations()->first()->pivot->follows_opening_hours);
    }

    #[Test]
    public function it_refuses_hours_that_overlap(): void
    {
        [$michael, $scranton] = $this->owner();
        $jim = $this->member($scranton, 'Jim');

        $response = $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $jim), [
            'days' => [
                2 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '14:00'], ['start' => '12:00', 'end' => '18:00']]],
            ],
        ]);

        $response->assertRedirect(route('onboarding.availability.staff.edit', $jim));
        $response->assertSessionHasErrors('days');
        $this->assertFalse($jim->workingHours()->exists());
    }

    #[Test]
    public function it_returns_not_found_for_somebody_who_cannot_be_booked(): void
    {
        [$michael, $scranton] = $this->owner();
        $pam = $this->member($scranton, 'Pam', ['is_bookable' => false]);

        $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $pam))->assertNotFound();
        $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $pam), ['days' => []])->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_somebody_not_working_at_the_headquarters(): void
    {
        [$michael] = $this->owner();
        $toby = Staff::factory()->create(['company_id' => $michael->company_id]);

        $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $toby))->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_team_member_of_another_company(): void
    {
        [$michael] = $this->owner();
        $nashua = Location::factory()->create(['is_headquarters' => true]);
        $creed = $this->member($nashua, 'Creed');

        $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $creed))->assertNotFound();
        $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $creed), ['days' => []])->assertNotFound();
    }

    /** @return array{0: User, 1: Location} */
    private function owner(): array
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin Cuts',
            'onboarding_step' => OnboardingStepEnum::Availability,
        ]);
        $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);

        return [$user->refresh(), $scranton];
    }

    /** @param array<string, mixed> $attributes */
    private function member(Location $location, string $firstName, array $attributes = []): Staff
    {
        $staff = Staff::factory()->create(['company_id' => $location->company_id, 'first_name' => $firstName, ...$attributes]);
        $staff->locations()->attach($location->id);

        return $staff;
    }
}
