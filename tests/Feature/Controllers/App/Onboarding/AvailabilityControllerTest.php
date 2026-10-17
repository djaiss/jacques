<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\AvailabilityControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Jobs\SendEmail;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows the availability step with the default week', function () {
    [$michael] = owner();

    $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

    $response->assertStatus(200);
    $response->assertSee('Onboarding · Step 4 of 4');
    $response->assertSee('When can clients come in?');
    $response->assertSee('Finish later');
    $response->assertSee('Finish setup');
    $response->assertSee(route('onboarding.availability.update'));
    expect($response->viewData('viewModel')->editor()['days'][3]['ranges'])->toEqual([['start' => '09:00', 'end' => '20:00']]);
});

it('shows the opening hours already stored', function () {
    [$michael, $scranton] = owner();
    LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '07:30:00', 'end_time' => '15:00:00']);

    $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

    $days = $response->viewData('viewModel')->editor()['days'];

    expect($days[1]['ranges'])->toEqual([['start' => '07:30', 'end' => '15:00']]);
    expect($days[3]['open'])->toBeFalse();
});

it('lists the bookable team and leaves out the others', function () {
    [$michael, $scranton] = owner();
    member($scranton, 'Jim');
    member($scranton, 'Pam', ['is_bookable' => false]);

    $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

    $response->assertSee('Jim');
    $response->assertDontSee('Pam');
    $response->assertSee('2 team members on salon opening hours.');
});

it('stores the week ends the onboarding and opens the completion screen', function () {
    Queue::fake();

    [$michael, $scranton, $michaelStaff] = owner();

    $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), [
        'days' => week(),
        'use_opening_hours' => '1',
    ]);

    $response->assertRedirect(route('onboarding.completion.index'));
    expect($michael->company->refresh()->is_onboarding_pending)->toBeFalse();
    $this->assertDatabaseMissing('location_opening_hours', ['location_id' => $scranton->id, 'day_of_week' => 1]);
    $this->assertDatabaseHas('location_opening_hours', ['location_id' => $scranton->id, 'day_of_week' => 6, 'start_time' => '13:00:00', 'end_time' => '17:00:00']);
    expect($scranton->openingHours()->count())->toEqual(6);
    expect($michaelStaff->workingHours()->count())->toEqual(6);
});

it('keeps custom hours unless everybody is given the opening hours', function () {
    Queue::fake();

    [$michael, $scranton] = owner();
    $jim = member($scranton, 'Jim', customHours: true);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 0]);

    $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => week(), 'use_opening_hours' => '0']);

    expect($jim->workingHours()->pluck('day_of_week')->all())->toEqual([0]);
});

it('gives the opening hours to somebody with custom hours when the box is ticked', function () {
    Queue::fake();

    [$michael, $scranton] = owner();
    $jim = member($scranton, 'Jim', customHours: true);
    StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 0]);

    $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => week(), 'use_opening_hours' => '1']);

    expect($jim->workingHours()->pluck('day_of_week')->all())->not->toContain(0);
    expect((bool) $jim->locations()->first()->pivot->follows_opening_hours)->toBeTrue();
});

it('refuses a range that ends before it starts', function () {
    [$michael, $scranton] = owner();
    $days = week();
    $days[4]['ranges'][0] = ['start' => '18:00', 'end' => '09:00'];

    $response = $this->actingAs($michael)->from(route('onboarding.availability.index'))->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

    $response->assertRedirect(route('onboarding.availability.index'));
    $response->assertSessionHasErrors('days');
    expect($scranton->openingHours()->exists())->toBeFalse();
    expect($michael->company->refresh()->is_onboarding_pending)->toBeTrue();
});

it('refuses a time off the half hour grid', function () {
    [$michael] = owner();
    $days = week();
    $days[2]['ranges'][0]['start'] = '09:15';

    $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

    $response->assertSessionHasErrors('days.2.ranges.0.start');
});

it('refuses an open day without hours', function () {
    [$michael, $scranton] = owner();
    $days = week();
    $days[3] = ['open' => '1'];

    $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

    $response->assertSessionHasErrors('days.3.ranges');
    expect($scranton->openingHours()->exists())->toBeFalse();
});

it('finishes the onboarding with every day closed', function () {
    Queue::fake();

    [$michael, $scranton, $michaelStaff] = owner();
    $days = array_map(fn (array $day): array => [...$day, 'open' => '0'], week());

    $response = $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => $days, 'use_opening_hours' => '1']);

    $response->assertRedirect(route('onboarding.completion.index'));
    expect($scranton->openingHours()->exists())->toBeFalse();
    expect(StaffWorkingHour::query()->where('staff_id', $michaelStaff->id)->exists())->toBeFalse();
    expect($michael->company->refresh()->is_onboarding_pending)->toBeFalse();
});

it('unticks the box when somebody has custom hours', function () {
    [$michael, $scranton] = owner();
    member($scranton, 'Jim', customHours: true);

    $response = $this->actingAs($michael)->get(route('onboarding.availability.index'));

    expect($response->viewData('viewModel')->isUsingOpeningHours())->toBeFalse();
    $response->assertSee('Custom hours');
    $response->assertSee('2 team members, 1 with custom hours.');
});

it('never shows the onboarding again once finished', function () {
    Queue::fake();

    [$michael] = owner();

    $this->actingAs($michael)->put(route('onboarding.availability.update'), ['days' => week(), 'use_opening_hours' => '1']);

    $this->actingAs($michael)->get(route('onboarding.index'))->assertRedirect(route('home.index'));
    $this->actingAs($michael)->get(route('onboarding.availability.index'))->assertRedirect(route('home.index'));
});

it('sends a user who is not the owner home', function () {
    [$michael] = owner();
    $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $michael->company_id]), Role::MANAGER);

    $this->actingAs($pam)->get(route('onboarding.availability.index'))->assertRedirect(route('home.index'));
    $this->actingAs($pam)->put(route('onboarding.availability.update'))->assertRedirect(route('home.index'));
});

it('sends a company still on the team step back to it', function () {
    [$michael] = owner();
    $michael->company->update(['onboarding_step' => OnboardingStepEnum::Staff]);

    $this->actingAs($michael)->get(route('onboarding.availability.index'))->assertRedirect(route('onboarding.staff.index'));
});

/** @return array{0: User, 1: Location, 2: Staff} */
function owner(): array
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin Cuts',
        'onboarding_step' => OnboardingStepEnum::Availability,
    ]);
    $user = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'position' => 0]);
    $staff->locations()->attach($scranton->id);

    return [$user->refresh(), $scranton, $staff];
}

/** @param array<string, mixed> $attributes */
function member(Location $location, string $firstName, array $attributes = [], bool $customHours = false): Staff
{
    $staff = Staff::factory()->create(['company_id' => $location->company_id, 'first_name' => $firstName, 'display_name' => null, 'position' => 1, ...$attributes]);
    $staff->locations()->attach($location->id, ['follows_opening_hours' => ! $customHours]);

    return $staff;
}

/** @return array<int, array{open: string, ranges: list<array{start: string, end: string}>}> */
function week(): array
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

it('sends no invitation when the onboarding ends', function () {
    Queue::fake();

    [$michael, $scranton] = owner();
    $jim = member($scranton, 'Jim');
    StaffInvitation::factory()->create(['staff_id' => $jim->id]);

    $this->actingAs($michael)->put(route('onboarding.availability.update'), [
        'days' => week(),
        'use_opening_hours' => '1',
    ]);

    Queue::assertNotPushed(SendEmail::class);
    expect($jim->invitation->refresh()->sent_at)->toBeNull();
});
