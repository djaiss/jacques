<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use App\ViewModels\Onboarding\StaffPanel;
use App\ViewModels\Onboarding\TeamViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_owner_first_with_every_service(): void
    {
        [$user, $scranton] = $this->owner();
        $haircut = $this->offer($scranton, 'Haircut');
        $shave = $this->offer($scranton, 'Shave');
        $jim = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null, 'email' => null, 'position' => 0]);
        $user->staff->locationServices()->attach([$haircut->id, $shave->id]);

        $rows = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->rows();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows[0]['isOwnRecord']);
        $this->assertEquals('All 2 services', $rows[0]['services']);
        $this->assertEquals('Account owner', $rows[0]['account']);
        $this->assertEquals($jim->id, $rows[1]['id']);
        $this->assertEquals('No email address', $rows[1]['subtitle']);
        $this->assertEquals('None yet', $rows[1]['services']);
        $this->assertTrue($rows[1]['hasNoService']);
        $this->assertEquals('No email, no account', $rows[1]['account']);
    }

    #[Test]
    public function it_describes_each_team_member(): void
    {
        [$user, $scranton] = $this->owner();
        $haircut = $this->offer($scranton, 'Haircut');
        $this->offer($scranton, 'Shave');
        $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'display_name' => 'Dwight K.', 'job_title' => 'Assistant to the regional manager', 'email' => 'dwight@dundermifflin.com']);
        $dwight->locationServices()->attach($haircut->id);
        StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => Role::factory()->create(['company_id' => $user->company_id])->id]);
        Staff::factory()->create(['company_id' => $user->company_id, 'email' => 'pam@dundermifflin.com', 'is_bookable' => false]);

        $rows = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->rows();

        $this->assertEquals('Dwight K.', $rows[1]['name']);
        $this->assertEquals('Dwight Schrute · Assistant to the regional manager · dwight@dundermifflin.com', $rows[1]['subtitle']);
        $this->assertEquals('1 service', $rows[1]['services']);
        $this->assertEquals('Invitation ready', $rows[1]['account']);
        $this->assertEquals('Not bookable', $rows[2]['services']);
        $this->assertFalse($rows[2]['hasNoService']);
        $this->assertEquals('Not invited', $rows[2]['account']);
    }

    #[Test]
    public function it_says_the_team_can_wait_while_the_owner_is_alone(): void
    {
        [$user] = $this->owner();

        $viewModel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff);

        $this->assertEquals('Just you for now. Add the people who take appointments or help run the salon.', $viewModel->summary());
        $this->assertEquals('You can add your team now or later from Settings.', $viewModel->footerStatus());
    }

    #[Test]
    public function it_counts_the_people_and_the_invitations_ready(): void
    {
        [$user] = $this->owner();
        $role = Role::factory()->create(['company_id' => $user->company_id]);
        $dwight = Staff::factory()->create(['company_id' => $user->company_id]);
        StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => $role->id]);

        $viewModel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff);

        $this->assertEquals('2 people at Dunder Mifflin Cuts.', $viewModel->summary());
        $this->assertEquals(1, $viewModel->invitationCount());
        $this->assertEquals('1 invitation ready.', $viewModel->footerStatus());
    }

    #[Test]
    public function it_says_no_invitation_is_prepared_for_a_team_without_one(): void
    {
        [$user] = $this->owner();
        Staff::factory()->create(['company_id' => $user->company_id]);

        $this->assertEquals('No invitations prepared.', new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->footerStatus());
    }

    #[Test]
    public function it_leaves_a_deactivated_team_member_out(): void
    {
        [$user] = $this->owner();
        Staff::factory()->create(['company_id' => $user->company_id, 'is_active' => false]);

        $this->assertCount(1, new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->rows());
    }

    #[Test]
    public function it_opens_no_panel_by_default(): void
    {
        [$user] = $this->owner();

        $this->assertNull(new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->panel());
    }

    #[Test]
    public function it_opens_an_empty_panel_to_add_somebody(): void
    {
        [$user, $scranton] = $this->owner();
        $cuts = ServiceCategory::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Cuts'], 'position' => 1]);
        $haircut = $this->offer($scranton, 'Haircut', $cuts);
        $this->offer($scranton, 'Consultation');

        $panel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff, isAddingStaff: true)->panel();

        $this->assertInstanceOf(StaffPanel::class, $panel);
        $this->assertTrue($panel->isNew());
        $this->assertEquals('Add a team member', $panel->eyebrow());
        $this->assertEquals('Cuts', $panel->categories()[0]['name']);
        $this->assertEquals([['id' => $haircut->id, 'name' => 'Haircut']], $panel->categories()[0]['services']);
        $this->assertEquals('Without a category', $panel->categories()[1]['name']);
        $this->assertEquals(2, $panel->serviceTotal());
        $this->assertEquals([], $panel->values()['services']);
        $this->assertTrue($panel->values()['is_bookable']);
    }

    #[Test]
    public function it_opens_the_panel_on_the_owner_without_removal_nor_invitation(): void
    {
        [$user] = $this->owner();

        $panel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff, openStaff: $user->staff)->panel();

        $this->assertTrue($panel->isOwnRecord());
        $this->assertEquals('Your profile', $panel->eyebrow());
        $this->assertNull($panel->destroyUrl());
        $this->assertFalse($panel->canBeInvited());
    }

    #[Test]
    public function it_opens_the_panel_on_a_colleague_with_their_invitation_role(): void
    {
        [$user] = $this->owner();
        $manager = Role::query()->where('company_id', $user->company_id)->where('slug', Role::MANAGER)->first();
        $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'display_name' => null, 'email' => 'dwight@dundermifflin.com']);
        StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => $manager->id]);

        $panel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff, openStaff: $dwight)->panel();

        $this->assertEquals('Edit team member', $panel->eyebrow());
        $this->assertEquals(route('onboarding.staff.members.destroy', $dwight), $panel->destroyUrl());
        $this->assertEquals('Dwight Schrute leaves the team and loses their services. Sure?', $panel->removalQuestion());
        $this->assertTrue($panel->values()['invite']);
        $this->assertEquals($manager->id, $panel->values()['role']);
        $this->assertEquals('Michael Scott invited you to join Dunder Mifflin Cuts', $panel->preview()['subject']);
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
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'display_name' => null, 'position' => 1]);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);

        return [$user->refresh(), $scranton];
    }

    private function offer(Location $location, string $name, ?ServiceCategory $category = null): LocationService
    {
        $service = Service::factory()->create([
            'company_id' => $location->company_id,
            'service_category_id' => $category?->id,
            'name' => ['en' => $name],
        ]);

        return LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $service->id]);
    }
}
