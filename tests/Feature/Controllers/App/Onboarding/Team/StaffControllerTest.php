<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Team;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_panel_to_add_somebody(): void
    {
        [$michael] = $this->owner();

        $response = $this->actingAs($michael)->get(route('onboarding.staff.members.new'));

        $response->assertStatus(200);
        $response->assertSee('Add a team member');
        $response->assertSee('Add to the team');
        $response->assertSee(route('onboarding.staff.members.create'));
    }

    #[Test]
    public function it_adds_somebody_at_the_headquarters_with_their_services(): void
    {
        Queue::fake();

        [$michael, $scranton] = $this->owner();
        $beardTrim = $this->offer($scranton);
        $shave = $this->offer($scranton);
        $this->offer($scranton);

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

        $this->assertEquals('Barber', $jim->job_title);
        $this->assertTrue($jim->activeLocations()->whereKey($scranton->id)->exists());
        $this->assertEqualsCanonicalizing([$beardTrim->id, $shave->id], $jim->locationServices()->pluck('location_services.id')->all());
        $this->assertNull($jim->invitation);
    }

    #[Test]
    public function it_adds_somebody_clients_cannot_book(): void
    {
        Queue::fake();

        [$michael] = $this->owner();

        $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
            'first_name' => 'Pam',
            'is_bookable' => '0',
        ])->assertRedirect(route('onboarding.staff.index'));

        $this->assertFalse(Staff::query()->where('first_name', 'Pam')->firstOrFail()->is_bookable);
    }

    #[Test]
    public function it_refuses_somebody_without_a_first_name(): void
    {
        [$michael] = $this->owner();

        $response = $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
            'first_name' => '',
            'last_name' => 'Halpert',
        ]);

        $response->assertRedirect(route('onboarding.staff.members.new'));
        $response->assertSessionHasErrors(['first_name' => 'A first name is needed.']);
        $this->assertDatabaseMissing('staff', ['last_name' => 'Halpert']);
    }

    #[Test]
    public function it_refuses_an_email_another_team_member_uses(): void
    {
        Queue::fake();

        [$michael] = $this->owner();
        Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);

        $response = $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
            'first_name' => 'Mose',
            'email' => 'dwight@dundermifflin.com',
        ]);

        $response->assertRedirect(route('onboarding.staff.members.new'));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('staff', ['first_name' => 'Mose']);
    }

    #[Test]
    public function it_refuses_a_service_of_another_company(): void
    {
        Queue::fake();

        [$michael] = $this->owner();

        $response = $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
            'first_name' => 'Jim',
            'services' => [LocationService::factory()->create()->id],
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('staff', ['first_name' => 'Jim']);
    }

    #[Test]
    public function it_sends_a_company_still_on_step_two_back(): void
    {
        [$michael] = $this->owner();
        $michael->company->update(['onboarding_step' => OnboardingStepEnum::Services]);

        $this->actingAs($michael)->get(route('onboarding.staff.members.new'))->assertRedirect(route('onboarding.services.index'));
    }

    #[Test]
    public function it_opens_the_panel_on_a_team_member(): void
    {
        [$michael] = $this->owner();
        $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight']);

        $response = $this->actingAs($michael)->get(route('onboarding.staff.members.edit', $dwight));

        $response->assertStatus(200);
        $response->assertSee('Edit team member');
        $response->assertSee('Remove from the team');
        $response->assertSee(route('onboarding.staff.members.update', $dwight));
    }

    #[Test]
    public function it_updates_a_team_member_and_their_services(): void
    {
        Queue::fake();

        [$michael, $scranton] = $this->owner();
        $haircut = $this->offer($scranton);
        $shave = $this->offer($scranton);
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

        $this->assertEquals('Dwight K.', $dwight->display_name);
        $this->assertEquals([$shave->id], $dwight->locationServices()->pluck('location_services.id')->all());
    }

    #[Test]
    public function it_keeps_the_email_of_the_owner_when_they_edit_themselves(): void
    {
        Queue::fake();

        [$michael] = $this->owner();

        $this->actingAs($michael)->put(route('onboarding.staff.members.update', $michael->staff), [
            'first_name' => 'Michael',
            'email' => 'prison.mike@dundermifflin.com',
            'is_bookable' => '1',
        ])->assertRedirect(route('onboarding.staff.index'));

        $this->assertEquals('michael@dundermifflin.com', $michael->staff->refresh()->email);
    }

    #[Test]
    public function it_removes_a_team_member(): void
    {
        Queue::fake();

        [$michael, $scranton] = $this->owner();
        $andy = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Andy']);
        $andy->locations()->attach($scranton->id);
        $andy->locationServices()->attach($this->offer($scranton)->id);

        $this->actingAs($michael)->delete(route('onboarding.staff.members.destroy', $andy))->assertRedirect(route('onboarding.staff.index'));

        $this->assertModelMissing($andy);
        $this->assertDatabaseMissing('staff_location_services', ['staff_id' => $andy->id]);
    }

    #[Test]
    public function it_never_removes_the_owner_themselves(): void
    {
        [$michael] = $this->owner();

        $this->actingAs($michael)->delete(route('onboarding.staff.members.destroy', $michael->staff))->assertNotFound();

        $this->assertModelExists($michael->staff);
    }

    #[Test]
    public function it_returns_not_found_for_a_team_member_of_another_company(): void
    {
        [$michael] = $this->owner();
        $stranger = Staff::factory()->create();

        $this->actingAs($michael)->get(route('onboarding.staff.members.edit', $stranger))->assertNotFound();
        $this->actingAs($michael)->put(route('onboarding.staff.members.update', $stranger), ['first_name' => 'Creed'])->assertNotFound();
        $this->actingAs($michael)->delete(route('onboarding.staff.members.destroy', $stranger))->assertNotFound();
    }

    #[Test]
    public function it_prepares_an_invitation_with_the_role_picked(): void
    {
        Queue::fake();
        Mail::fake();

        [$michael] = $this->owner();
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
    }

    #[Test]
    public function it_prepares_no_invitation_without_an_email(): void
    {
        Queue::fake();

        [$michael] = $this->owner();

        $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
            'first_name' => 'Kevin',
            'invite' => '1',
            'role' => Role::query()->where('company_id', $michael->company_id)->value('id'),
        ])->assertRedirect(route('onboarding.staff.index'));

        $this->assertDatabaseCount('staff_invitations', 0);
    }

    #[Test]
    public function it_withdraws_the_invitation_when_the_box_is_unticked(): void
    {
        Queue::fake();

        [$michael] = $this->owner();
        $dwight = $this->invited($michael);

        $this->actingAs($michael)->put(route('onboarding.staff.members.update', $dwight), [
            'first_name' => 'Dwight',
            'email' => 'dwight@dundermifflin.com',
            'invite' => '0',
        ])->assertRedirect(route('onboarding.staff.index'));

        $this->assertNull($dwight->refresh()->invitation);
    }

    #[Test]
    public function it_withdraws_the_invitation_when_the_email_is_removed(): void
    {
        Queue::fake();

        [$michael] = $this->owner();
        $dwight = $this->invited($michael);

        $this->actingAs($michael)->put(route('onboarding.staff.members.update', $dwight), [
            'first_name' => 'Dwight',
            'email' => '',
            'invite' => '1',
        ])->assertRedirect(route('onboarding.staff.index'));

        $this->assertNull($dwight->refresh()->invitation);
    }

    #[Test]
    public function it_returns_not_found_for_a_role_of_another_company(): void
    {
        Queue::fake();

        [$michael] = $this->owner();

        $this->actingAs($michael)->post(route('onboarding.staff.members.create'), [
            'first_name' => 'Dwight',
            'email' => 'dwight@dundermifflin.com',
            'invite' => '1',
            'role' => Role::factory()->create()->id,
        ])->assertNotFound();

        $this->assertDatabaseMissing('staff', ['first_name' => 'Dwight']);
    }

    /** @return array{0: User, 1: Location} */
    private function owner(): array
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin Cuts',
            'onboarding_step' => OnboardingStepEnum::Staff,
            'languages' => ['en'],
        ]);
        $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'email' => 'michael@dundermifflin.com']);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);

        return [$user->refresh(), $scranton];
    }

    private function offer(Location $location): LocationService
    {
        $service = Service::factory()->create(['company_id' => $location->company_id]);

        return LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $service->id]);
    }

    private function invited(User $michael): Staff
    {
        $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight', 'email' => 'dwight@dundermifflin.com']);
        StaffInvitation::factory()->create([
            'staff_id' => $dwight->id,
            'role_id' => Role::query()->where('company_id', $michael->company_id)->value('id'),
        ]);

        return $dwight;
    }
}
