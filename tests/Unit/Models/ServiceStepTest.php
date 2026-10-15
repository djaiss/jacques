<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Service;
use App\Models\ServiceStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceStepTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_service(): void
    {
        $service = Service::factory()->create();
        $step = ServiceStep::factory()->create(['service_id' => $service->id]);

        $this->assertTrue($step->service()->exists());
        $this->assertEquals($service->id, $step->service->id);
    }

    #[Test]
    public function it_has_no_processing_time_unless_told_otherwise(): void
    {
        $service = Service::factory()->create();

        $this->assertSame(0, $service->steps->first()->processing_duration);
    }
}
