<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding\StepViewModelTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\User;
use App\ViewModels\Onboarding\StepViewModel;

it('names the salon and the step', function () {
    $viewModel = new StepViewModel(user: user(OnboardingStepEnum::Staff), step: OnboardingStepEnum::Services);

    expect($viewModel->salonName())->toEqual('Dunder Mifflin');
    expect($viewModel->stepLabel())->toEqual('Services');
    expect($viewModel->stepCounter())->toEqual('Step 2 of 4');
});

it('marks the steps done current and upcoming', function () {
    $viewModel = new StepViewModel(user: user(OnboardingStepEnum::Staff), step: OnboardingStepEnum::Services);

    $steps = $viewModel->steps();

    expect($steps)->toHaveCount(4);
    expect(array_column($steps, 'isDone'))->toEqual([true, true, false, false]);
    expect(array_column($steps, 'isCurrent'))->toEqual([false, true, false, false]);
    expect(array_column($steps, 'label'))->toEqual(['Location', 'Services', 'Staff', 'Availability']);
});

it('links only the steps already reached', function () {
    $viewModel = new StepViewModel(user: user(OnboardingStepEnum::Services), step: OnboardingStepEnum::Location);

    expect(array_column($viewModel->steps(), 'url'))->toEqual([route('onboarding.location.index'), route('onboarding.services.index'), null, null]);
});

function user(OnboardingStepEnum $reached): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => $reached,
    ]);

    return User::factory()->create(['company_id' => $company->id]);
}
