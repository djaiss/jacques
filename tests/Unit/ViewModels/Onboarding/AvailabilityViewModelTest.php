<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding\AvailabilityViewModelTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use App\ViewModels\Onboarding\AvailabilityViewModel;
use App\ViewModels\Onboarding\StaffHoursPanel;

it('offers the default week monday first', function () {
    [$michael] = salon();

    $days = viewModel($michael)->editor()['days'];

    expect(array_column($days, 'day'))->toEqual([1, 2, 3, 4, 5, 6, 0]);
    expect($days[0]['short'])->toEqual('Mon');
    expect($days[6]['name'])->toEqual('Sunday');
    expect($days[5]['ranges'])->toEqual([['start' => '09:00', 'end' => '17:00']]);
    expect($days[6]['open'])->toBeFalse();
    expect($days[6]['ranges'])->toEqual([['start' => '09:00', 'end' => '18:00']]);
});

it('shows the stored week with sunday last', function () {
    [$michael, $scranton] = salon();
    LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 0, 'start_time' => '10:00:00', 'end_time' => '14:00:00']);

    $days = viewModel($michael)->editor()['days'];

    expect($days[6]['open'])->toBeTrue();
    expect($days[6]['ranges'])->toEqual([['start' => '10:00', 'end' => '14:00']]);
    expect($days[0]['open'])->toBeFalse();
});

it('offers every half hour from six to ten in the evening', function () {
    [$michael] = salon();

    $times = viewModel($michael)->editor()['times'];

    expect($times)->toHaveCount(33);
    expect($times[0])->toEqual(['value' => '06:00', 'label' => '6:00']);
    expect($times[32])->toEqual(['value' => '22:00', 'label' => '22:00']);
});

it('writes a summary for every number of open days', function () {
    [$michael] = salon();

    $summaries = viewModel($michael)->editor()['sentences']['summaries'];

    expect($summaries[0])->toEqual('Closed all week');
    expect($summaries[1])->toEqual('1 day, :hours h a week');
    expect($summaries[6])->toEqual('6 days, :hours h a week');
});

it('lists the bookable team with the author first', function () {
    [$michael, $scranton] = salon();
    $jim = member($scranton, ['first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null, 'position' => 0]);
    member($scranton, ['first_name' => 'Pam', 'is_bookable' => false]);
    member($scranton, ['first_name' => 'Toby', 'is_active' => false]);
    $own = member($scranton, ['user_id' => $michael->id, 'first_name' => 'Michael', 'position' => 5]);

    $rows = viewModel($michael)->teamRows();

    expect(array_column($rows, 'id'))->toEqual([$own->id, $jim->id]);
    expect($rows[1]['follows'])->toBeTrue();
});

it('sums up custom hours and unticks the box', function () {
    [$michael, $scranton] = salon();
    $jim = member($scranton, ['first_name' => 'Jim'], customHours: true);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '13:00:00', 'end_time' => '17:30:00']);

    $viewModel = viewModel($michael);

    expect($viewModel->isUsingOpeningHours())->toBeFalse();
    expect($viewModel->teamRows()[0]['summary'])->toEqual('1 day, 7.5 h a week');
    expect($viewModel->teamStatus())->toEqual('1 team member, 1 with custom hours.');
});

it('says when nobody can be booked', function () {
    [$michael] = salon();

    expect(viewModel($michael)->teamOnOpeningHoursStatus())->toEqual('Nobody on the team can be booked yet.');
});

it('opens the panel on a team member', function () {
    [$michael, $scranton] = salon();
    $jim = member($scranton, ['first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);

    $panel = new AvailabilityViewModel(user: $michael, step: OnboardingStepEnum::Availability, openStaff: $jim)->panel();

    expect($panel)->toBeInstanceOf(StaffHoursPanel::class);
    expect($panel->name())->toEqual('Jim Halpert');
    expect($panel->follows())->toBeTrue();
    expect($panel->week())->toHaveCount(7);
    expect(viewModel($michael)->panel())->toBeNull();
});

/** @return array{0: User, 1: Location} */
function salon(): array
{
    $company = Company::factory()->onboardingPending()->create(['onboarding_step' => OnboardingStepEnum::Availability]);
    $michael = User::factory()->create(['company_id' => $company->id]);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'is_headquarters' => true]);

    return [$michael, $scranton];
}

/** @param array<string, mixed> $attributes */
function member(Location $location, array $attributes, bool $customHours = false): Staff
{
    $staff = Staff::factory()->create(['company_id' => $location->company_id, ...$attributes]);
    $staff->locations()->attach($location->id, ['follows_opening_hours' => ! $customHours]);

    return $staff;
}

function viewModel(User $user): AvailabilityViewModel
{
    return new AvailabilityViewModel(user: $user, step: OnboardingStepEnum::Availability);
}
