<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\StaffControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('shows the staff step', function () {
    $user = owner();

    $response = $this->actingAs($user)->get(route('onboarding.staff.index'));

    $response->assertStatus(200);
    $response->assertSee('Onboarding · Step 3 of 4');
    $response->assertSee('Who works at Dunder Mifflin?');
    $response->assertSee('Finish later');
    $response->assertSee('I’ll set this up later');
    $response->assertSee('Continue to availability');
    $response->assertSee('You can add your team now or later from Settings.');
    $response->assertSee(route('onboarding.staff.update'));
    $response->assertSee(route('onboarding.staff.members.new'));
});

it('sends no email when the step is completed with invitations ready', function () {
    Queue::fake();
    Mail::fake();

    $user = owner();
    $dwight = Staff::factory()->create(['company_id' => $user->company_id, 'email' => 'dwight@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => Role::query()->where('company_id', $user->company_id)->value('id')]);

    $this->actingAs($user)->put(route('onboarding.staff.update'))->assertRedirect(route('onboarding.availability.index'));

    Mail::assertNothingOutgoing();
    $this->assertModelExists($dwight->invitation);
});

it('completes the staff step', function () {
    Queue::fake();

    $user = owner();

    $response = $this->actingAs($user)->put(route('onboarding.staff.update'));

    $response->assertRedirect(route('onboarding.availability.index'));

    expect($user->company->refresh()->onboarding_step)->toEqual(OnboardingStepEnum::Availability);
    expect($user->company->is_onboarding_pending)->toBeTrue();
});

it('sends a user who is not the owner home', function () {
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Staff,
    ]);
    $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    $this->actingAs($pam)->get(route('onboarding.staff.index'))->assertRedirect(route('home.index'));
    $this->actingAs($pam)->put(route('onboarding.staff.update'))->assertRedirect(route('home.index'));
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Staff,
    ]);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    return test()->giveDefaultRole($user, Role::OWNER);
}
