<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Availability\StaffHoursControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('opens the panel on a team member', function () {
    [$michael, $scranton] = owner();
    $jim = member($scranton, 'Jim');

    $response = $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $jim));

    $response->assertStatus(200);
    $response->assertSee('Working hours');
    $response->assertSee('Save hours');
    $response->assertSee('Start again from salon hours');
    $response->assertSee(route('onboarding.availability.staff.update', $jim));
});

it('stores the hours of a team member as their own', function () {
    Queue::fake();

    [$michael, $scranton] = owner();
    $jim = member($scranton, 'Jim');

    $response = $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $jim), [
        'days' => [
            0 => ['open' => '1', 'ranges' => [['start' => '10:00', 'end' => '14:00']]],
            2 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '18:00']]],
            3 => ['open' => '0', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
        ],
    ]);

    $response->assertRedirect(route('onboarding.availability.index'));
    expect($jim->workingHours()->orderBy('day_of_week')->pluck('day_of_week')->all())->toEqual([0, 2, 2]);
    expect((bool) $jim->locations()->first()->pivot->follows_opening_hours)->toBeFalse();
});

it('refuses hours that overlap', function () {
    [$michael, $scranton] = owner();
    $jim = member($scranton, 'Jim');

    $response = $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $jim), [
        'days' => [
            2 => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '14:00'], ['start' => '12:00', 'end' => '18:00']]],
        ],
    ]);

    $response->assertRedirect(route('onboarding.availability.staff.edit', $jim));
    $response->assertSessionHasErrors('days');
    expect($jim->workingHours()->exists())->toBeFalse();
});

it('returns not found for somebody who cannot be booked', function () {
    [$michael, $scranton] = owner();
    $pam = member($scranton, 'Pam', ['is_bookable' => false]);

    $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $pam))->assertNotFound();
    $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $pam), ['days' => []])->assertNotFound();
});

it('returns not found for somebody not working at the headquarters', function () {
    [$michael] = owner();
    $toby = Staff::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $toby))->assertNotFound();
});

it('returns not found for a team member of another company', function () {
    [$michael] = owner();
    $nashua = Location::factory()->create(['is_headquarters' => true]);
    $creed = member($nashua, 'Creed');

    $this->actingAs($michael)->get(route('onboarding.availability.staff.edit', $creed))->assertNotFound();
    $this->actingAs($michael)->put(route('onboarding.availability.staff.update', $creed), ['days' => []])->assertNotFound();
});

/** @return array{0: User, 1: Location} */
function owner(): array
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin Cuts',
        'onboarding_step' => OnboardingStepEnum::Availability,
    ]);
    $user = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);

    return [$user->refresh(), $scranton];
}

/** @param array<string, mixed> $attributes */
function member(Location $location, string $firstName, array $attributes = []): Staff
{
    $staff = Staff::factory()->create(['company_id' => $location->company_id, 'first_name' => $firstName, ...$attributes]);
    $staff->locations()->attach($location->id);

    return $staff;
}
