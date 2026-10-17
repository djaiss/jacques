<?php

declare(strict_types=1);

use App\Enums\OnboardingStepEnum;

it('names every step', function () {
    foreach (OnboardingStepEnum::cases() as $step) {
        expect($step->label())->not->toBeEmpty();
    }
});

it('orders the four steps', function () {
    expect(array_map(fn (OnboardingStepEnum $step): string => $step->label(), OnboardingStepEnum::cases()))->toEqual(['Location', 'Services', 'Staff', 'Availability']);
});

it('gives every step a route that exists', function () {
    foreach (OnboardingStepEnum::cases() as $step) {
        expect(app('router')->has($step->routeName()))->toBeTrue();
    }
});

it('gives the step that follows', function () {
    expect(OnboardingStepEnum::Location->next())->toEqual(OnboardingStepEnum::Services);
    expect(OnboardingStepEnum::Staff->next())->toEqual(OnboardingStepEnum::Availability);
    expect(OnboardingStepEnum::Availability->next())->toBeNull();
});
