<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\LocationService;
use App\Models\Staff;
use App\Models\StaffLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_staff_member(): void
    {
        $staff = Staff::factory()->create();
        $assignment = StaffLocationService::factory()->create(['staff_id' => $staff->id]);

        $this->assertTrue($assignment->staff()->exists());
        $this->assertEquals($staff->id, $assignment->staff->id);
    }

    #[Test]
    public function it_belongs_to_a_location_service(): void
    {
        $locationService = LocationService::factory()->create();
        $assignment = StaffLocationService::factory()->create(['location_service_id' => $locationService->id]);

        $this->assertTrue($assignment->locationService()->exists());
        $this->assertEquals($locationService->id, $assignment->locationService->id);
    }
}
