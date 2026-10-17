<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding\TeamViewModelTest;

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

it('lists the owner first with every service', function () {
    [$user, $scranton] = owner();
    $haircut = offer($scranton, 'Haircut');
    $shave = offer($scranton, 'Shave');
    $jim = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null, 'email' => null, 'position' => 0]);
    $user->staff->locationServices()->attach([$haircut->id, $shave->id]);

    $rows = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->rows();

    expect($rows)->toHaveCount(2);
    expect($rows[0]['isOwnRecord'])->toBeTrue();
    expect($rows[0]['services'])->toEqual('All 2 services');
    expect($rows[0]['account'])->toEqual('Account owner');
    expect($rows[1]['id'])->toEqual($jim->id);
    expect($rows[1]['subtitle'])->toEqual('No email address');
    expect($rows[1]['services'])->toEqual('None yet');
    expect($rows[1]['hasNoService'])->toBeTrue();
    expect($rows[1]['account'])->toEqual('No email, no account');
});

it('describes each team member', function () {
    [$user, $scranton] = owner();
    $haircut = offer($scranton, 'Haircut');
    offer($scranton, 'Shave');
    $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'display_name' => 'Dwight K.', 'job_title' => 'Assistant to the regional manager', 'email' => 'dwight@dundermifflin.com']);
    $dwight->locationServices()->attach($haircut->id);
    StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => Role::factory()->create(['company_id' => $user->company_id])->id]);
    Staff::factory()->create(['company_id' => $user->company_id, 'email' => 'pam@dundermifflin.com', 'is_bookable' => false]);

    $rows = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->rows();

    expect($rows[1]['name'])->toEqual('Dwight K.');
    expect($rows[1]['subtitle'])->toEqual('Dwight Schrute · Assistant to the regional manager · dwight@dundermifflin.com');
    expect($rows[1]['services'])->toEqual('1 service');
    expect($rows[1]['account'])->toEqual('Invitation ready');
    expect($rows[2]['services'])->toEqual('Not bookable');
    expect($rows[2]['hasNoService'])->toBeFalse();
    expect($rows[2]['account'])->toEqual('Not invited');
});

it('says the team can wait while the owner is alone', function () {
    [$user] = owner();

    $viewModel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff);

    expect($viewModel->summary())->toEqual('Just you for now. Add the people who take appointments or help run the salon.');
    expect($viewModel->footerStatus())->toEqual('You can add your team now or later from Settings.');
});

it('counts the people and the invitations ready', function () {
    [$user] = owner();
    $role = Role::factory()->create(['company_id' => $user->company_id]);
    $dwight = Staff::factory()->create(['company_id' => $user->company_id]);
    StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => $role->id]);

    $viewModel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff);

    expect($viewModel->summary())->toEqual('2 people at Dunder Mifflin Cuts.');
    expect($viewModel->invitationCount())->toEqual(1);
    expect($viewModel->footerStatus())->toEqual('1 invitation ready.');
});

it('says no invitation is prepared for a team without one', function () {
    [$user] = owner();
    Staff::factory()->create(['company_id' => $user->company_id]);

    expect(new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->footerStatus())->toEqual('No invitations prepared.');
});

it('leaves a deactivated team member out', function () {
    [$user] = owner();
    Staff::factory()->create(['company_id' => $user->company_id, 'is_active' => false]);

    expect(new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->rows())->toHaveCount(1);
});

it('opens no panel by default', function () {
    [$user] = owner();

    expect(new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff)->panel())->toBeNull();
});

it('opens an empty panel to add somebody', function () {
    [$user, $scranton] = owner();
    $cuts = ServiceCategory::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Cuts'], 'position' => 1]);
    $haircut = offer($scranton, 'Haircut', $cuts);
    offer($scranton, 'Consultation');

    $panel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff, isAddingStaff: true)->panel();

    expect($panel)->toBeInstanceOf(StaffPanel::class);
    expect($panel->isNew())->toBeTrue();
    expect($panel->eyebrow())->toEqual('Add a team member');
    expect($panel->categories()[0]['name'])->toEqual('Cuts');
    expect($panel->categories()[0]['services'])->toEqual([['id' => $haircut->id, 'name' => 'Haircut']]);
    expect($panel->categories()[1]['name'])->toEqual('Without a category');
    expect($panel->serviceTotal())->toEqual(2);
    expect($panel->values()['services'])->toEqual([]);
    expect($panel->values()['is_bookable'])->toBeTrue();
});

it('opens the panel on the owner without removal nor invitation', function () {
    [$user] = owner();

    $panel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff, openStaff: $user->staff)->panel();

    expect($panel->isOwnRecord())->toBeTrue();
    expect($panel->eyebrow())->toEqual('Your profile');
    expect($panel->destroyUrl())->toBeNull();
    expect($panel->canBeInvited())->toBeFalse();
});

it('opens the panel on a colleague with their invitation role', function () {
    [$user] = owner();
    $manager = Role::query()->where('company_id', $user->company_id)->where('slug', Role::MANAGER)->first();
    $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'display_name' => null, 'email' => 'dwight@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => $manager->id]);

    $panel = new TeamViewModel(user: $user, step: OnboardingStepEnum::Staff, openStaff: $dwight)->panel();

    expect($panel->eyebrow())->toEqual('Edit team member');
    expect($panel->destroyUrl())->toEqual(route('onboarding.staff.members.destroy', $dwight));
    expect($panel->removalQuestion())->toEqual('Dwight Schrute leaves the team and loses their services. Sure?');
    expect($panel->values()['invite'])->toBeTrue();
    expect($panel->values()['role'])->toEqual($manager->id);
    expect($panel->preview()['subject'])->toEqual('Michael Scott has invited you to join Dunder Mifflin Cuts');
    expect($panel->preview()['greeting'])->toEqual('Hi :first_name,');
    expect($panel->preview()['button'])->toEqual('Accept invitation');
    expect($panel->preview()['expiry'])->toEqual('This invitation expires in 7 days.');
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
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'display_name' => null, 'position' => 1]);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton', 'is_headquarters' => true]);

    return [$user->refresh(), $scranton];
}

function offer(Location $location, string $name, ?ServiceCategory $category = null): LocationService
{
    $service = Service::factory()->create([
        'company_id' => $location->company_id,
        'service_category_id' => $category?->id,
        'name' => ['en' => $name],
    ]);

    return LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $service->id]);
}
