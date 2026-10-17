<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Models\Staff;

class StaffHoursPanel
{
    /**
     * @param  list<array{day: int, short: string, name: string, open: bool, ranges: list<array{start: string, end: string}>}>  $week
     */
    public function __construct(
        private readonly Staff $staff,
        private readonly array $week,
        private readonly bool $follows,
    ) {}

    public function key(): int
    {
        return $this->staff->id;
    }

    public function name(): string
    {
        return $this->staff->name;
    }

    /**
     * The week the panel starts from. Somebody who follows the opening hours
     * starts from them as they are on the screen, so the browser fills it.
     *
     * @return list<array{day: int, short: string, name: string, open: bool, ranges: list<array{start: string, end: string}>}>
     */
    public function week(): array
    {
        return $this->week;
    }

    public function follows(): bool
    {
        return $this->follows;
    }

    public function formAction(): string
    {
        return route('onboarding.availability.staff.update', $this->staff);
    }
}
