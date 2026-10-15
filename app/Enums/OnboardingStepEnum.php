<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum OnboardingStepEnum: int implements Translatable
{
    case Location = 1;
    case Services = 2;
    case Staff = 3;
    case Appointments = 4;

    public function label(): string
    {
        return match ($this) {
            self::Location => 'Location',
            self::Services => 'Services',
            self::Staff => 'Staff',
            self::Appointments => 'Appointments',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Location => 'onboarding.location.index',
            self::Services => 'onboarding.services.index',
            self::Staff => 'onboarding.staff.index',
            self::Appointments => 'onboarding.appointments.index',
        };
    }

    public function next(): ?self
    {
        return self::tryFrom($this->value + 1);
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
