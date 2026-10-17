<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\ServiceControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows the services step', function () {
    $user = owner();

    $response = $this->actingAs($user)->get(route('onboarding.services.index'));

    $response->assertStatus(200);
    $response->assertSee('Onboarding · Step 2 of 4');
    $response->assertSee('What services do you offer?');
    $response->assertSee('Finish later');
    $response->assertSee(route('onboarding.services.update'));
});

it('lists the categories and services of the catalogue', function () {
    $user = owner();
    $category = ServiceCategory::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Cuts']]);
    Service::factory()->create(['company_id' => $user->company_id, 'service_category_id' => $category->id, 'name' => ['en' => 'Dwight cut'], 'prices' => ['CAD' => 4250]]);

    $response = $this->actingAs($user)->get(route('onboarding.services.index'));

    $response->assertStatus(200);
    $response->assertSee('Cuts');
    $response->assertSee('Dwight cut');
    $response->assertSee('value="42.50"', false);
    $response->assertSee('1 service in your catalog');
});

it('shows the empty catalogue', function () {
    $user = owner();

    $response = $this->actingAs($user)->get(route('onboarding.services.index'));

    $response->assertSee('Your catalog is empty');
    $response->assertSee('Use the example catalog');
});

it('offers every service at the headquarters when the step is completed', function () {
    Queue::fake();

    $user = owner();
    $scranton = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => true]);
    $service = Service::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->put(route('onboarding.services.update'));

    $this->assertDatabaseHas('location_services', ['location_id' => $scranton->id, 'service_id' => $service->id]);
});

it('completes the services step', function () {
    Queue::fake();

    $user = owner();

    $response = $this->actingAs($user)->put(route('onboarding.services.update'));

    $response->assertRedirect(route('onboarding.staff.index'));

    expect($user->company->refresh()->onboarding_step)->toEqual(OnboardingStepEnum::Staff);
    expect($user->company->is_onboarding_pending)->toBeTrue();
});

it('sends a user who is not the owner home', function () {
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
    ]);
    $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    $this->actingAs($pam)->get(route('onboarding.services.index'))->assertRedirect(route('home.index'));
    $this->actingAs($pam)->put(route('onboarding.services.update'))->assertRedirect(route('home.index'));
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => OnboardingStepEnum::Services,
    ]);
    $user = User::factory()->create(['company_id' => $company->id]);

    return test()->giveDefaultRole($user, Role::OWNER);
}
