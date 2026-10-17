<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use App\ViewModels\Onboarding\AvailabilityViewModel;
use App\ViewModels\Onboarding\StaffHoursPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailabilityViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_default_week_monday_first(): void
    {
        [$michael] = $this->salon();

        $days = $this->viewModel($michael)->editor()['days'];

        $this->assertEquals([1, 2, 3, 4, 5, 6, 0], array_column($days, 'day'));
        $this->assertEquals('Mon', $days[0]['short']);
        $this->assertEquals('Sunday', $days[6]['name']);
        $this->assertEquals([['start' => '09:00', 'end' => '17:00']], $days[5]['ranges']);
        $this->assertFalse($days[6]['open']);
        $this->assertEquals([['start' => '09:00', 'end' => '18:00']], $days[6]['ranges']);
    }

    #[Test]
    public function it_shows_the_stored_week_with_sunday_last(): void
    {
        [$michael, $scranton] = $this->salon();
        LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 0, 'start_time' => '10:00:00', 'end_time' => '14:00:00']);

        $days = $this->viewModel($michael)->editor()['days'];

        $this->assertTrue($days[6]['open']);
        $this->assertEquals([['start' => '10:00', 'end' => '14:00']], $days[6]['ranges']);
        $this->assertFalse($days[0]['open']);
    }

    #[Test]
    public function it_offers_every_half_hour_from_six_to_ten_in_the_evening(): void
    {
        [$michael] = $this->salon();

        $times = $this->viewModel($michael)->editor()['times'];

        $this->assertCount(33, $times);
        $this->assertEquals(['value' => '06:00', 'label' => '6:00'], $times[0]);
        $this->assertEquals(['value' => '22:00', 'label' => '22:00'], $times[32]);
    }

    #[Test]
    public function it_writes_a_summary_for_every_number_of_open_days(): void
    {
        [$michael] = $this->salon();

        $summaries = $this->viewModel($michael)->editor()['sentences']['summaries'];

        $this->assertEquals('Closed all week', $summaries[0]);
        $this->assertEquals('1 day, :hours h a week', $summaries[1]);
        $this->assertEquals('6 days, :hours h a week', $summaries[6]);
    }

    #[Test]
    public function it_lists_the_bookable_team_with_the_author_first(): void
    {
        [$michael, $scranton] = $this->salon();
        $jim = $this->member($scranton, ['first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null, 'position' => 0]);
        $this->member($scranton, ['first_name' => 'Pam', 'is_bookable' => false]);
        $this->member($scranton, ['first_name' => 'Toby', 'is_active' => false]);
        $own = $this->member($scranton, ['user_id' => $michael->id, 'first_name' => 'Michael', 'position' => 5]);

        $rows = $this->viewModel($michael)->teamRows();

        $this->assertEquals([$own->id, $jim->id], array_column($rows, 'id'));
        $this->assertTrue($rows[1]['follows']);
    }

    #[Test]
    public function it_sums_up_custom_hours_and_unticks_the_box(): void
    {
        [$michael, $scranton] = $this->salon();
        $jim = $this->member($scranton, ['first_name' => 'Jim'], customHours: true);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '13:00:00', 'end_time' => '17:30:00']);

        $viewModel = $this->viewModel($michael);

        $this->assertFalse($viewModel->isUsingOpeningHours());
        $this->assertEquals('1 day, 7.5 h a week', $viewModel->teamRows()[0]['summary']);
        $this->assertEquals('1 team member, 1 with custom hours.', $viewModel->teamStatus());
    }

    #[Test]
    public function it_says_when_nobody_can_be_booked(): void
    {
        [$michael] = $this->salon();

        $this->assertEquals('Nobody on the team can be booked yet.', $this->viewModel($michael)->teamOnOpeningHoursStatus());
    }

    #[Test]
    public function it_opens_the_panel_on_a_team_member(): void
    {
        [$michael, $scranton] = $this->salon();
        $jim = $this->member($scranton, ['first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);

        $panel = new AvailabilityViewModel(user: $michael, step: OnboardingStepEnum::Availability, openStaff: $jim)->panel();

        $this->assertInstanceOf(StaffHoursPanel::class, $panel);
        $this->assertEquals('Jim Halpert', $panel->name());
        $this->assertTrue($panel->follows());
        $this->assertCount(7, $panel->week());
        $this->assertNull($this->viewModel($michael)->panel());
    }

    /** @return array{0: User, 1: Location} */
    private function salon(): array
    {
        $company = Company::factory()->onboardingPending()->create(['onboarding_step' => OnboardingStepEnum::Availability]);
        $michael = User::factory()->create(['company_id' => $company->id]);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'is_headquarters' => true]);

        return [$michael, $scranton];
    }

    /** @param array<string, mixed> $attributes */
    private function member(Location $location, array $attributes, bool $customHours = false): Staff
    {
        $staff = Staff::factory()->create(['company_id' => $location->company_id, ...$attributes]);
        $staff->locations()->attach($location->id, ['follows_opening_hours' => ! $customHours]);

        return $staff;
    }

    private function viewModel(User $user): AvailabilityViewModel
    {
        return new AvailabilityViewModel(user: $user, step: OnboardingStepEnum::Availability);
    }
}
