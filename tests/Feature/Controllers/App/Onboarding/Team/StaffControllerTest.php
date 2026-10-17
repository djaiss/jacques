<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Team\StaffControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('opens the panel to add somebody', function () {
    [$michael] = owner();

    $response = $this->actingAs($michael)->get(route('onboarding.staff.members.new'));

    $response->assertStatus(200);
    $response->assertSee('Add a team member');
    $response->assertSee('Add to the team');
    $response->assertSee(route('onboarding.staff.members.create'));
});

it('adds somebody at the headquarters with their services', function () {
    Queue::fake();

    [$michael, $scranton] = owner();
    $beardTrim = offer($scranton);
    $shave = offer($scranton);
    offer($scranton);

    $response = $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => 'Jim',
        'last_name' => 'Halpert',
        'job_title' => 'Barber',
        'email' => 'jim@dundermifflin.com',
        'is_bookable' => '1',
        'services' => [$beardTrim->id, $shave->id],
        'invite' => '0',
    ]);

    $response->assertRedirect(route('onboarding.staff.index'));

    $jim = Staff::query()->where('first_name', 'Jim')->firstOrFail();

    expect($jim->job_title)->toEqual('Barber');
    expect($jim->activeLocations()->whereKey($scranton->id)->exists())->toBeTrue();
    expect($jim->locationServices()->pluck('location_services.id')->all())->toEqualCanonicalizing([$beardTrim->id, $shave->id]);
    expect($jim->invitation)->toBeNull();
});

it('adds somebody clients cannot book', function () {
    Queue::fake();

    [$michael] = owner();

    $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => 'Pam',
        'is_bookable' => '0',
    ])->assertRedirect(route('onboarding.staff.index'));

    expect(Staff::query()->where('first_name', 'Pam')->firstOrFail()->is_bookable)->toBeFalse();
});

it('refuses somebody without a first name', function () {
    [$michael] = owner();

    $response = $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => '',
        'last_name' => 'Halpert',
    ]);

    $response->assertRedirect(route('onboarding.staff.members.new'));
    $response->assertSessionHasErrors(['first_name' => 'A first name is needed.']);
    $this->assertDatabaseMissing('staff', ['last_name' => 'Halpert']);
});

it('refuses an email another team member uses', function () {
    Queue::fake();

    [$michael] = owner();
    Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);

    $response = $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => 'Mose',
        'email' => 'dwight@dundermifflin.com',
    ]);

    $response->assertRedirect(route('onboarding.staff.members.new'));
    $response->assertSessionHasErrors('email');
    $this->assertDatabaseMissing('staff', ['first_name' => 'Mose']);
});

it('refuses a service of another company', function () {
    Queue::fake();

    [$michael] = owner();

    $response = $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => 'Jim',
        'services' => [LocationService::factory()->create()->id],
    ]);

    $response->assertNotFound();
    $this->assertDatabaseMissing('staff', ['first_name' => 'Jim']);
});

it('sends a company still on step two back', function () {
    [$michael] = owner();
    $michael->company->update(['onboarding_step' => OnboardingStepEnum::Services]);

    $this->actingAs($michael)->get(route('onboarding.staff.members.new'))->assertRedirect(route('onboarding.services.index'));
});

it('opens the panel on a team member', function () {
    [$michael] = owner();
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight']);

    $response = $this->actingAs($michael)->get(route('onboarding.staff.members.edit', $dwight));

    $response->assertStatus(200);
    $response->assertSee('Edit team member');
    $response->assertSee('Remove from the team');
    $response->assertSee(route('onboarding.staff.members.update', $dwight));
});

it('updates a team member and their services', function () {
    Queue::fake();

    [$michael, $scranton] = owner();
    $haircut = offer($scranton);
    $shave = offer($scranton);
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight']);
    $dwight->locations()->attach($scranton->id);
    $dwight->locationServices()->attach([$haircut->id, $shave->id]);

    $response = $this->actingAs($michael)->put(route('onboarding.staff.members.update', $dwight), [
        'first_name' => 'Dwight',
        'last_name' => 'Schrute',
        'display_name' => 'Dwight K.',
        'is_bookable' => '1',
        'services' => [$shave->id],
    ]);

    $response->assertRedirect(route('onboarding.staff.index'));

    $dwight->refresh();

    expect($dwight->display_name)->toEqual('Dwight K.');
    expect($dwight->locationServices()->pluck('location_services.id')->all())->toEqual([$shave->id]);
});

it('keeps the email of the owner when they edit themselves', function () {
    Queue::fake();

    [$michael] = owner();

    $this->actingAs($michael)->put(route('onboarding.staff.members.update', $michael->staff), [
        'first_name' => 'Michael',
        'email' => 'prison.mike@dundermifflin.com',
        'is_bookable' => '1',
    ])->assertRedirect(route('onboarding.staff.index'));

    expect($michael->staff->refresh()->email)->toEqual('michael@dundermifflin.com');
});

it('removes a team member', function () {
    Queue::fake();

    [$michael, $scranton] = owner();
    $andy = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Andy']);
    $andy->locations()->attach($scranton->id);
    $andy->locationServices()->attach(offer($scranton)->id);

    $this->actingAs($michael)->delete(route('onboarding.staff.members.destroy', $andy))->assertRedirect(route('onboarding.staff.index'));

    $this->assertModelMissing($andy);
    $this->assertDatabaseMissing('staff_location_services', ['staff_id' => $andy->id]);
});

it('never removes the owner themselves', function () {
    [$michael] = owner();

    $this->actingAs($michael)->delete(route('onboarding.staff.members.destroy', $michael->staff))->assertNotFound();

    $this->assertModelExists($michael->staff);
});

it('returns not found for a team member of another company', function () {
    [$michael] = owner();
    $stranger = Staff::factory()->create();

    $this->actingAs($michael)->get(route('onboarding.staff.members.edit', $stranger))->assertNotFound();
    $this->actingAs($michael)->put(route('onboarding.staff.members.update', $stranger), ['first_name' => 'Creed'])->assertNotFound();
    $this->actingAs($michael)->delete(route('onboarding.staff.members.destroy', $stranger))->assertNotFound();
});

it('prepares an invitation with the role picked', function () {
    Queue::fake();
    Mail::fake();

    [$michael] = owner();
    $manager = Role::query()->where('company_id', $michael->company_id)->where('slug', Role::MANAGER)->firstOrFail();

    $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => 'Dwight',
        'email' => 'dwight@dundermifflin.com',
        'invite' => '1',
        'role' => $manager->id,
    ])->assertRedirect(route('onboarding.staff.index'));

    $dwight = Staff::query()->where('first_name', 'Dwight')->firstOrFail();

    $this->assertDatabaseHas('staff_invitations', ['staff_id' => $dwight->id, 'role_id' => $manager->id, 'author_id' => $michael->id]);
    $this->actingAs($michael)->get(route('onboarding.staff.index'))->assertSee('Invitation ready');
    Mail::assertNothingOutgoing();
});

it('prepares no invitation without an email', function () {
    Queue::fake();

    [$michael] = owner();

    $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => 'Kevin',
        'invite' => '1',
        'role' => Role::query()->where('company_id', $michael->company_id)->value('id'),
    ])->assertRedirect(route('onboarding.staff.index'));

    $this->assertDatabaseCount('staff_invitations', 0);
});

it('withdraws the invitation when the box is unticked', function () {
    Queue::fake();

    [$michael] = owner();
    $dwight = invited($michael);

    $this->actingAs($michael)->put(route('onboarding.staff.members.update', $dwight), [
        'first_name' => 'Dwight',
        'email' => 'dwight@dundermifflin.com',
        'invite' => '0',
    ])->assertRedirect(route('onboarding.staff.index'));

    expect($dwight->refresh()->invitation)->toBeNull();
});

it('withdraws the invitation when the email is removed', function () {
    Queue::fake();

    [$michael] = owner();
    $dwight = invited($michael);

    $this->actingAs($michael)->put(route('onboarding.staff.members.update', $dwight), [
        'first_name' => 'Dwight',
        'email' => '',
        'invite' => '1',
    ])->assertRedirect(route('onboarding.staff.index'));

    expect($dwight->refresh()->invitation)->toBeNull();
});

it('returns not found for a role of another company', function () {
    Queue::fake();

    [$michael] = owner();

    $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
        'first_name' => 'Dwight',
        'email' => 'dwight@dundermifflin.com',
        'invite' => '1',
        'role' => Role::factory()->create()->id,
    ])->assertNotFound();

    $this->assertDatabaseMissing('staff', ['first_name' => 'Dwight']);
});

/** @return array{0: User, 1: Location} */
function owner(): array
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin Cuts',
        'onboarding_step' => OnboardingStepEnum::Staff,
        'languages' => ['en'],
    ]);
    $user = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'email' => 'michael@dundermifflin.com']);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);

    return [$user->refresh(), $scranton];
}

function offer(Location $location): LocationService
{
    $service = Service::factory()->create(['company_id' => $location->company_id]);

    return LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $service->id]);
}

function invited(User $michael): Staff
{
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight', 'email' => 'dwight@dundermifflin.com']);
    StaffInvitation::factory()->create([
        'staff_id' => $dwight->id,
        'role_id' => Role::query()->where('company_id', $michael->company_id)->value('id'),
    ]);

    return $dwight;
}
