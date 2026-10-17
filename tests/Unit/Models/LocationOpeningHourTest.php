<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\LocationOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationOpeningHourTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_location(): void
    {
        $openingHour = LocationOpeningHour::factory()->create();

        $this->assertTrue($openingHour->location()->exists());
    }

    #[Test]
    public function it_casts_the_day_of_the_week_to_an_integer(): void
    {
        $openingHour = LocationOpeningHour::factory()->create(['day_of_week' => '6']);

        $this->assertSame(6, $openingHour->refresh()->day_of_week);
    }
}
