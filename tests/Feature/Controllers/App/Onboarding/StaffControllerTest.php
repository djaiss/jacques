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

class StaffControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_staff_step(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->get(route('onboarding.staff.index'));

        $response->assertStatus(200);
        $response->assertSee('Onboarding · Step 3 of 4');
        $response->assertSee('Finish later');
        $response->assertSee(route('onboarding.staff.update'));
    }

    #[Test]
    public function it_completes_the_staff_step(): void
    {
        Queue::fake();

        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.staff.update'));

        $response->assertRedirect(route('onboarding.appointments.index'));

        $this->assertEquals(OnboardingStepEnum::Appointments, $user->company->refresh()->onboarding_step);
        $this->assertTrue($user->company->is_onboarding_pending);
    }

    #[Test]
    public function it_sends_a_user_who_is_not_the_owner_home(): void
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Staff,
        ]);
        $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        $this->actingAs($pam)->get(route('onboarding.staff.index'))->assertRedirect(route('home.index'));
        $this->actingAs($pam)->put(route('onboarding.staff.update'))->assertRedirect(route('home.index'));
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Staff,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        return $this->giveDefaultRole($user, Role::OWNER);
    }
}
