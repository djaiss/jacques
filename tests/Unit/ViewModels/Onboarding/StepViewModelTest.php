<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\User;
use App\ViewModels\Onboarding\StepViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StepViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_salon_and_the_step(): void
    {
        $viewModel = new StepViewModel(user: $this->user(OnboardingStepEnum::Staff), step: OnboardingStepEnum::Services);

        $this->assertEquals('Dunder Mifflin', $viewModel->salonName());
        $this->assertEquals('Services', $viewModel->stepLabel());
        $this->assertEquals('Step 2 of 4', $viewModel->stepCounter());
    }

    #[Test]
    public function it_marks_the_steps_done_current_and_upcoming(): void
    {
        $viewModel = new StepViewModel(user: $this->user(OnboardingStepEnum::Staff), step: OnboardingStepEnum::Services);

        $steps = $viewModel->steps();

        $this->assertCount(4, $steps);
        $this->assertEquals([true, true, false, false], array_column($steps, 'isDone'));
        $this->assertEquals([false, true, false, false], array_column($steps, 'isCurrent'));
        $this->assertEquals(['Location', 'Services', 'Staff', 'Availability'], array_column($steps, 'label'));
    }

    #[Test]
    public function it_links_only_the_steps_already_reached(): void
    {
        $viewModel = new StepViewModel(user: $this->user(OnboardingStepEnum::Services), step: OnboardingStepEnum::Location);

        $this->assertEquals(
            [route('onboarding.location.index'), route('onboarding.services.index'), null, null],
            array_column($viewModel->steps(), 'url'),
        );
    }

    private function user(OnboardingStepEnum $reached): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => $reached,
        ]);

        return User::factory()->create(['company_id' => $company->id]);
    }
}
