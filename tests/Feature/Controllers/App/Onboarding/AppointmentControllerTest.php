<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_appointments_step(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->get(route('onboarding.appointments.index'));

        $response->assertStatus(200);
        $response->assertSee('Onboarding · Step 4 of 4');
        $response->assertSee('Finish later');
        $response->assertSee(route('onboarding.appointments.update'));
    }

    #[Test]
    public function it_completes_the_appointments_step(): void
    {
        Queue::fake();

        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.appointments.update'));

        $response->assertRedirect(route('home.index'));

        $this->assertFalse($user->company->refresh()->is_onboarding_pending);
    }

    #[Test]
    public function it_never_shows_the_onboarding_again_once_finished(): void
    {
        Queue::fake();

        $user = $this->owner();

        $this->actingAs($user)->put(route('onboarding.appointments.update'));

        $this->actingAs($user)->get(route('onboarding.index'))->assertRedirect(route('home.index'));
        $this->actingAs($user)->get(route('onboarding.location.index'))->assertRedirect(route('home.index'));
        $this->actingAs($user)->get(route('onboarding.appointments.index'))->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_sends_a_user_who_is_not_the_owner_home(): void
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Appointments,
        ]);
        $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        $this->actingAs($pam)->get(route('onboarding.appointments.index'))->assertRedirect(route('home.index'));
        $this->actingAs($pam)->put(route('onboarding.appointments.update'))->assertRedirect(route('home.index'));
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Appointments,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        return $this->giveDefaultRole($user, Role::OWNER);
    }
}
