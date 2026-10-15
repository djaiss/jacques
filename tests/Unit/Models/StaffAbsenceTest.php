<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Location;
use App\Models\StaffAbsence;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffAbsenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_staff_member(): void
    {
        $absence = StaffAbsence::factory()->create();

        $this->assertTrue($absence->staff()->exists());
    }

    #[Test]
    public function it_belongs_to_a_location_when_it_concerns_only_one(): void
    {
        $absence = StaffAbsence::factory()->create(['location_id' => Location::factory()->create()->id]);

        $this->assertTrue($absence->location()->exists());
        $this->assertFalse($absence->concernsEveryLocation());
    }

    #[Test]
    public function it_concerns_every_location_without_one(): void
    {
        $absence = StaffAbsence::factory()->create(['location_id' => null]);

        $this->assertFalse($absence->location()->exists());
        $this->assertTrue($absence->concernsEveryLocation());
    }

    #[Test]
    public function it_casts_its_dates(): void
    {
        $absence = StaffAbsence::factory()->create()->refresh();

        $this->assertInstanceOf(Carbon::class, $absence->starts_at);
        $this->assertInstanceOf(Carbon::class, $absence->ends_at);
    }
}
