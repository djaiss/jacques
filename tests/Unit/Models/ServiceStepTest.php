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
    public function it_requires_a_team_member_unless_told_otherwise(): void
    {
        $service = Service::factory()->create();

        $this->assertTrue($service->steps->first()->requires_staff);
        $this->assertSame(30, $service->steps->first()->duration);
    }

    #[Test]
    public function it_casts_its_name_per_language(): void
    {
        $step = ServiceStep::factory()->create(['name' => ['en' => 'Processing', 'fr' => 'Temps de pose']]);

        $this->assertSame(['en' => 'Processing', 'fr' => 'Temps de pose'], $step->refresh()->name);
    }
}
