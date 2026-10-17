<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_availability_step_with_the_default_week(): void
    {
        [$michael] = $this->owner();

        $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

        $response->assertStatus(200);
        $response->assertSee('Onboarding · Step 4 of 4');
        $response->assertSee('When can clients come in?');
        $response->assertSee('Finish later');
        $response->assertSee('Finish setup');
        $response->assertSee(route('onboarding.availability.update'));
        $this->assertEquals([['start' => '09:00', 'end' => '20:00']], $response->viewData('viewModel')->editor()['days'][3]['ranges']);
    }

    #[Test]
    public function it_shows_the_opening_hours_already_stored(): void
    {
        [$michael, $scranton] = $this->owner();
        LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '07:30:00', 'end_time' => '15:00:00']);

        $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

        $days = $response->viewData('viewModel')->editor()['days'];

        $this->assertEquals([['start' => '07:30', 'end' => '15:00']], $days[1]['ranges']);
        $this->assertFalse($days[3]['open']);
    }

    #[Test]
    public function it_lists_the_bookable_team_and_leaves_out_the_others(): void
    {
        [$michael, $scranton] = $this->owner();
        $this->member($scranton, 'Jim');
        $this->member($scranton, 'Pam', ['is_bookable' => false]);

        $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

        $response->assertSee('Jim');
        $response->assertDontSee('Pam');
        $response->assertSee('2 team members on salon opening hours.');
    }

    #[Test]
    public function it_stores_the_week_ends_the_onboarding_and_opens_the_dashboard(): void
    {
        Queue::fake();

        [$michael, $scranton, $michaelStaff] = $this->owner();

        $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), [
            'days' => $this->week(),
            'use_opening_hours' => '1',
        ]);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertFalse($michael->company->refresh()->is_onboarding_pending);
        $this->assertDatabaseMissing('location_opening_hours', ['location_id' => $scranton->id, 'day_of_week' => 1]);
        $this->assertDatabaseHas('location_opening_hours', ['location_id' => $scranton->id, 'day_of_week' => 6, 'start_time' => '13:00:00', 'end_time' => '17:00:00']);
        $this->assertEquals(6, $scranton->openingHours()->count());
        $this->assertEquals(6, $michaelStaff->workingHours()->count());
    }

    #[Test]
    public function it_keeps_custom_hours_unless_everybody_is_given_the_opening_hours(): void
    {
        Queue::fake();

        [$michael, $scranton] = $this->owner();
        $jim = $this->member($scranton, 'Jim', customHours: true);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 0]);

        $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $this->week(), 'use_opening_hours' => '0']);

        $this->assertEquals([0], $jim->workingHours()->pluck('day_of_week')->all());
    }

    #[Test]
    public function it_gives_the_opening_hours_to_somebody_with_custom_hours_when_the_box_is_ticked(): void
    {
        Queue::fake();

        [$michael, $scranton] = $this->owner();
        $jim = $this->member($scranton, 'Jim', customHours: true);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 0]);

        $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $this->week(), 'use_opening_hours' => '1']);

        $this->assertNotContains(0, $jim->workingHours()->pluck('day_of_week')->all());
        $this->assertTrue((bool) $jim->locations()->first()->pivot->follows_opening_hours);
    }

    #[Test]
    public function it_refuses_a_range_that_ends_before_it_starts(): void
    {
        [$michael, $scranton] = $this->owner();
        $days = $this->week();
        $days[4]['ranges'][0] = ['start' => '18:00', 'end' => '09:00'];

        $response = $this->actingAs($michael)->from(route('onboarding.availability.index'))->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

        $response->assertRedirect(route('onboarding.availability.index'));
        $response->assertSessionHasErrors('days');
        $this->assertFalse($scranton->openingHours()->exists());
        $this->assertTrue($michael->company->refresh()->is_onboarding_pending);
    }

    #[Test]
    public function it_refuses_a_time_off_the_half_hour_grid(): void
    {
        [$michael] = $this->owner();
        $days = $this->week();
        $days[2]['ranges'][0]['start'] = '09:15';

        $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

        $response->assertSessionHasErrors('days.2.ranges.0.start');
    }

    #[Test]
    public function it_refuses_an_open_day_without_hours(): void
    {
        [$michael, $scranton] = $this->owner();
        $days = $this->week();
        $days[3] = ['open' => '1'];

        $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

        $response->assertSessionHasErrors('days.3.ranges');
        $this->assertFalse($scranton->openingHours()->exists());
    }

    #[Test]
    public function it_finishes_the_onboarding_with_every_day_closed(): void
    {
        Queue::fake();

        [$michael, $scranton, $michaelStaff] = $this->owner();
        $days = array_map(fn (array $day): array => [...$day, 'open' => '0'], $this->week());

        $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertFalse($scranton->openingHours()->exists());
        $this->assertFalse(StaffWorkingHour::query()->where('staff_id', $michaelStaff->id)->exists());
        $this->assertFalse($michael->company->refresh()->is_onboarding_pending);
    }

    #[Test]
    public function it_unticks_the_box_when_somebody_has_custom_hours(): void
    {
        [$michael, $scranton] = $this->owner();
        $this->member($scranton, 'Jim', customHours: true);

        $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

        $this->assertFalse($response->viewData('viewModel')->isUsingOpeningHours());
        $response->assertSee('Custom hours');
        $response->assertSee('2 team members, 1 with custom hours.');
    }

    #[Test]
    public function it_never_shows_the_onboarding_again_once_finished(): void
    {
        Queue::fake();

        [$michael] = $this->owner();

        $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $this->week(), 'use_opening_hours' => '1']);

        $this->actingAs($michael)->get(route('onboarding.index'))->assertRedirect(route('home.index'));
        $this->actingAs($michael)->get(route('onboarding.availability.index'))->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_sends_a_user_who_is_not_the_owner_home(): void
    {
        [$michael] = $this->owner();
        $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $michael->company_id]), Role::MANAGER);

        $this->actingAs($pam)->get(route('onboarding.availability.index'))->assertRedirect(route('home.index'));
        $this->actingAs($pam)->put(route('onboarding.availability.update'))->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_sends_a_company_still_on_the_team_step_back_to_it(): void
    {
        [$michael] = $this->owner();
        $michael->company->update(['onboarding_step' => OnboardingStepEnum::Staff]);

        $this->actingAs($michael)->get(route('onboarding.availability.index'))->assertRedirect(route('onboarding.staff.index'));
    }

    /** @return array{0: User, 1: Location, 2: Staff} */
    private function owner(): array
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin Cuts',
            'onboarding_step' => OnboardingStepEnum::Availability,
        ]);
        $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);
        $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'position' => 0]);
        $staff->locations()->attach($scranton->id);

        return [$user->refresh(), $scranton, $staff];
    }

    /** @param array<string, mixed> $attributes */
    private function member(Location $location, string $firstName, array $attributes = [], bool $customHours = false): Staff
    {
        $staff = Staff::factory()->create(['company_id' => $location->company_id, 'first_name' => $firstName, 'display_name' => null, 'position' => 1, ...$attributes]);
        $staff->locations()->attach($location->id, ['follows_opening_hours' => ! $customHours]);

        return $staff;
    }

    /** @return array<int, array{open: string, ranges: list<array{start: string, end: string}>}> */
    private function week(): array
    {
        return [
            0 => ['open' => '0', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
            1 => ['open' => '0', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
            2 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
            3 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
            4 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '20:00']]],
            5 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '20:00']]],
            6 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '17:00']]],
        ];
    }
}
