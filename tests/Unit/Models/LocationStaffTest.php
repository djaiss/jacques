<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationStaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_staff_member(): void
    {
        $staff = Staff::factory()->create();
        $assignment = LocationStaff::factory()->create(['staff_id' => $staff->id]);

        $this->assertTrue($assignment->staff()->exists());
        $this->assertEquals($staff->id, $assignment->staff->id);
    }

    #[Test]
    public function it_belongs_to_a_location(): void
    {
        $location = Location::factory()->create();
        $assignment = LocationStaff::factory()->create(['location_id' => $location->id]);

        $this->assertTrue($assignment->location()->exists());
        $this->assertEquals($location->id, $assignment->location->id);
    }

    #[Test]
    public function it_says_when_the_staff_member_was_assigned(): void
    {
        $assignment = LocationStaff::factory()->create();

        $this->assertNotNull($assignment->created_at);
        $this->assertEqualsWithDelta(now()->timestamp, $assignment->created_at->timestamp, 2);
    }

    #[Test]
    public function it_casts_is_active_to_a_boolean(): void
    {
        $active = LocationStaff::factory()->create()->refresh();
        $paused = LocationStaff::factory()->paused()->create()->refresh();

        $this->assertTrue($active->is_active);
        $this->assertFalse($paused->is_active);
    }
}
