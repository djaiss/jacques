<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\WeeklyHours;
use App\Models\LocationOpeningHour;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeeklyHoursTest extends TestCase
{
    #[Test]
    public function it_accepts_a_day_closed_for_lunch(): void
    {
        WeeklyHours::validate([6 => [['start' => '13:00', 'end' => '17:00'], ['start' => '09:00', 'end' => '12:00']]]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_accepts_two_ranges_that_touch(): void
    {
        WeeklyHours::validate([1 => [['start' => '09:00', 'end' => '12:00'], ['start' => '12:00', 'end' => '18:00']]]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_refuses_a_range_that_ends_when_it_starts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WeeklyHours::validate([1 => [['start' => '09:00', 'end' => '09:00']]]);
    }

    #[Test]
    public function it_refuses_an_hour_past_midnight(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WeeklyHours::validate([1 => [['start' => '09:00', 'end' => '24:00']]]);
    }

    #[Test]
    public function it_builds_the_week_from_stored_rows(): void
    {
        $rows = collect([
            new LocationOpeningHour(['day_of_week' => 6, 'start_time' => '13:00:00', 'end_time' => '17:00:00']),
            new LocationOpeningHour(['day_of_week' => 6, 'start_time' => '09:00:00', 'end_time' => '12:00:00']),
        ]);

        $week = WeeklyHours::fromRows($rows);

        $this->assertCount(7, $week);
        $this->assertEquals([], $week[0]);
        $this->assertEquals([['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '17:00']], $week[6]);
    }

    #[Test]
    public function it_builds_the_week_from_a_form_and_leaves_out_closed_days(): void
    {
        $week = WeeklyHours::fromForm([
            '0' => ['open' => '0', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
            '1' => ['open' => '1', 'ranges' => [['start' => '09:00', 'end' => '18:00']]],
        ]);

        $this->assertEquals([], $week[0]);
        $this->assertEquals([['start' => '09:00', 'end' => '18:00']], $week[1]);
        $this->assertEquals([], $week[6]);
    }
}
