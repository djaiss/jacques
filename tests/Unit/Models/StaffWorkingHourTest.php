<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\StaffWorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffWorkingHourTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_staff_member(): void
    {
        $workingHour = StaffWorkingHour::factory()->create();

        $this->assertTrue($workingHour->staff()->exists());
    }

    #[Test]
    public function it_belongs_to_a_location(): void
    {
        $workingHour = StaffWorkingHour::factory()->create();

        $this->assertTrue($workingHour->location()->exists());
    }

    #[Test]
    public function it_casts_the_day_of_the_week_to_an_integer(): void
    {
        $workingHour = StaffWorkingHour::factory()->create(['day_of_week' => '3']);

        $this->assertSame(3, $workingHour->refresh()->day_of_week);
    }
}
