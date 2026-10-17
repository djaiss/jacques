<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\User;

class StepViewModel
{
    public function __construct(
        protected readonly User $user,
        protected readonly OnboardingStepEnum $step,
    ) {}

    public function salonName(): string
    {
        return $this->user->company->name;
    }

    public function stepLabel(): string
    {
        return __($this->step->label());
    }

    public function stepCounter(): string
    {
        return __('Step :number of :total', [
            'number' => $this->step->value,
            'total' => count(OnboardingStepEnum::cases()),
        ]);
    }

    /** @return array<int, array{number: int, label: string, url: string|null, isDone: bool, isCurrent: bool, hue: int}> */
    public function steps(): array
    {
        $reached = $this->user->company->onboarding_step;

        return array_map(fn (OnboardingStepEnum $step): array => [
            'number' => $step->value,
            'label' => __($step->label()),
            'url' => $step->value <= $reached->value ? route($step->routeName()) : null,
            'isDone' => $step->value < $reached->value,
            'isCurrent' => $step === $this->step,
            'hue' => match ($step) {
                OnboardingStepEnum::Location => 30,
                OnboardingStepEnum::Services => 80,
                OnboardingStepEnum::Staff => 250,
                OnboardingStepEnum::Availability => 310,
            },
        ], OnboardingStepEnum::cases());
    }
}
