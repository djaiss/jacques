<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CompleteOnboardingStep;
use App\Enums\OnboardingStepEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompleteOnboardingStepTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_company_to_the_next_step(): void
    {
        Queue::fake();

        $user = $this->owner();

        $result = new CompleteOnboardingStep(
            author: $user,
            step: OnboardingStepEnum::Location,
        )->execute();

        $this->assertInstanceOf(Company::class, $result);
        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'onboarding_step' => OnboardingStepEnum::Services->value,
            'is_onboarding_pending' => true,
        ]);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_never_moves_the_company_back(): void
    {
        Queue::fake();

        $user = $this->owner();
        $user->company->update(['onboarding_step' => OnboardingStepEnum::Appointments]);

        new CompleteOnboardingStep(
            author: $user,
            step: OnboardingStepEnum::Location,
        )->execute();

        $this->assertEquals(OnboardingStepEnum::Appointments, $user->company->refresh()->onboarding_step);
    }

    #[Test]
    public function it_finishes_the_onboarding_on_the_last_step(): void
    {
        Queue::fake();

        $user = $this->owner();
        $user->company->update(['onboarding_step' => OnboardingStepEnum::Appointments]);

        new CompleteOnboardingStep(
            author: $user,
            step: OnboardingStepEnum::Appointments,
        )->execute();

        $this->assertFalse($user->company->refresh()->is_onboarding_pending);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OnboardingCompleted
                && $job->company->id === $user->company_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_does_nothing_once_the_onboarding_is_finished(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->giveDefaultRole($user, Role::OWNER);

        new CompleteOnboardingStep(
            author: $user,
            step: OnboardingStepEnum::Appointments,
        )->execute();

        $company->refresh();

        $this->assertFalse($company->is_onboarding_pending);
        $this->assertEquals(OnboardingStepEnum::Location, $company->onboarding_step);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_a_user_who_cannot_manage_the_company(): void
    {
        $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->expectException(ModelNotFoundException::class);

        new CompleteOnboardingStep(
            author: $user,
            step: OnboardingStepEnum::Location,
        )->execute();
    }

    #[Test]
    public function it_offers_every_service_at_the_headquarters_when_the_services_step_is_completed(): void
    {
        Queue::fake();

        $user = $this->owner();
        $scranton = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => true]);
        $stamford = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => false]);
        $haircut = Service::factory()->create(['company_id' => $user->company_id]);

        new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();
        new CompleteOnboardingStep(author: $user, step: OnboardingStepEnum::Services)->execute();

        $this->assertEquals(1, LocationService::query()->where('location_id', $scranton->id)->where('service_id', $haircut->id)->count());
        $this->assertDatabaseMissing('location_services', ['location_id' => $stamford->id]);
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        return $this->giveDefaultRole($user, Role::OWNER);
    }
}
