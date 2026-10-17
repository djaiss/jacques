<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_owner_to_the_step_they_reached(): void
    {
        $user = $this->owner(OnboardingStepEnum::Staff);

        $this->actingAs($user)->get(route('onboarding.index'))->assertRedirect(route('onboarding.staff.index'));
    }

    #[Test]
    public function it_starts_a_new_company_on_the_first_step(): void
    {
        $user = $this->owner(OnboardingStepEnum::Location);

        $this->actingAs($user)->get(route('onboarding.index'))->assertRedirect(route('onboarding.location.index'));
    }

    #[Test]
    public function it_sends_a_step_beyond_the_one_reached_back_to_it(): void
    {
        $user = $this->owner(OnboardingStepEnum::Services);

        $this->actingAs($user)->get(route('onboarding.availability.index'))->assertRedirect(route('onboarding.services.index'));
        $this->actingAs($user)->put(route('onboarding.staff.update'))->assertRedirect(route('onboarding.services.index'));
    }

    #[Test]
    public function it_lets_the_owner_revisit_a_step_already_done(): void
    {
        $user = $this->owner(OnboardingStepEnum::Staff);

        $this->actingAs($user)->get(route('onboarding.location.index'))->assertStatus(200);
    }

    #[Test]
    public function it_leaves_the_owner_free_to_use_the_application(): void
    {
        $user = $this->owner(OnboardingStepEnum::Services);

        $this->actingAs($user)->get(route('settings.index'))->assertStatus(200);
        $this->assertEquals(OnboardingStepEnum::Services, $user->company->refresh()->onboarding_step);
    }

    #[Test]
    public function it_sends_a_finished_company_home(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

        $this->actingAs($user)->get(route('onboarding.index'))->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_sends_a_user_who_is_not_the_owner_home(): void
    {
        $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
        $jim = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        $this->actingAs($jim)->get(route('onboarding.index'))->assertRedirect(route('home.index'));
    }

    private function owner(OnboardingStepEnum $reached): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => $reached,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        return $this->giveDefaultRole($user, Role::OWNER);
    }
}
