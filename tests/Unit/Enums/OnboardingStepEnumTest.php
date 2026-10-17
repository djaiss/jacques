<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OnboardingStepEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingStepEnumTest extends TestCase
{
    #[Test]
    public function it_names_every_step(): void
    {
        foreach (OnboardingStepEnum::cases() as $step) {
            $this->assertNotEmpty($step->label());
        }
    }

    #[Test]
    public function it_orders_the_four_steps(): void
    {
        $this->assertEquals(
            ['Location', 'Services', 'Staff', 'Availability'],
            array_map(fn (OnboardingStepEnum $step): string => $step->label(), OnboardingStepEnum::cases()),
        );
    }

    #[Test]
    public function it_gives_every_step_a_route_that_exists(): void
    {
        foreach (OnboardingStepEnum::cases() as $step) {
            $this->assertTrue(app('router')->has($step->routeName()));
        }
    }

    #[Test]
    public function it_gives_the_step_that_follows(): void
    {
        $this->assertEquals(OnboardingStepEnum::Services, OnboardingStepEnum::Location->next());
        $this->assertEquals(OnboardingStepEnum::Availability, OnboardingStepEnum::Staff->next());
        $this->assertNull(OnboardingStepEnum::Availability->next());
    }
}
