<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_location(): void
    {
        $location = Location::factory()->create();
        $locationService = LocationService::factory()->create(['location_id' => $location->id]);

        $this->assertTrue($locationService->location()->exists());
        $this->assertEquals($location->id, $locationService->location->id);
    }

    #[Test]
    public function it_belongs_to_a_service_of_the_same_company(): void
    {
        $locationService = LocationService::factory()->create();

        $this->assertTrue($locationService->service()->exists());
        $this->assertEquals($locationService->location->company_id, $locationService->service->company_id);
    }

    #[Test]
    public function it_belongs_to_many_team_members(): void
    {
        $locationService = LocationService::factory()->create();
        $jim = User::factory()->create(['company_id' => $locationService->location->company_id]);
        $locationService->users()->attach($jim->id);

        $this->assertTrue($locationService->users()->exists());
        $this->assertEquals($jim->id, $locationService->users->first()->id);
    }

    #[Test]
    public function it_charges_its_own_price_in_the_currencies_it_replaces(): void
    {
        $location = Location::factory()->create();
        $haircut = Service::factory()->create(['company_id' => $location->company_id, 'prices' => ['CAD' => 3500, 'USD' => 2600]]);
        $locationService = LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $haircut->id, 'prices' => ['USD' => 3000]]);

        $this->assertEquals(3000, $locationService->resolvedPrice('USD'));
        $this->assertEquals(3500, $locationService->resolvedPrice('CAD'));
    }

    #[Test]
    public function it_follows_the_price_of_the_service_without_one(): void
    {
        $location = Location::factory()->create();
        $haircut = Service::factory()->create(['company_id' => $location->company_id, 'prices' => ['CAD' => 3500]]);
        $locationService = LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $haircut->id]);

        $haircut->update(['prices' => ['CAD' => 3800]]);

        $this->assertEquals(3800, $locationService->refresh()->resolvedPrice('CAD'));
    }

    #[Test]
    public function it_has_no_price_in_a_currency_neither_it_nor_the_service_has(): void
    {
        $location = Location::factory()->create();
        $haircut = Service::factory()->create(['company_id' => $location->company_id, 'prices' => ['CAD' => 3500]]);
        $locationService = LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $haircut->id]);

        $this->assertNull($locationService->resolvedPrice('EUR'));
    }
}
