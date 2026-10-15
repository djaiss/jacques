<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\OfferServicesAtLocation;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferServicesAtLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_every_active_service_at_the_price_of_the_service(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $haircut = Service::factory()->create(['company_id' => $company->id]);
        $beardTrim = Service::factory()->create(['company_id' => $company->id]);

        new OfferServicesAtLocation(location: $scranton)->execute();

        $this->assertEquals(2, LocationService::query()->where('location_id', $scranton->id)->count());
        $this->assertDatabaseHas('location_services', [
            'location_id' => $scranton->id,
            'service_id' => $haircut->id,
            'prices' => null,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('location_services', [
            'location_id' => $scranton->id,
            'service_id' => $beardTrim->id,
        ]);
    }

    #[Test]
    public function it_skips_a_deactivated_service(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $balayage = Service::factory()->create(['company_id' => $company->id, 'is_active' => false]);

        new OfferServicesAtLocation(location: $scranton)->execute();

        $this->assertDatabaseMissing('location_services', ['service_id' => $balayage->id]);
    }

    #[Test]
    public function it_does_not_offer_a_service_twice(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $haircut = Service::factory()->create(['company_id' => $company->id]);
        LocationService::factory()->create([
            'location_id' => $scranton->id,
            'service_id' => $haircut->id,
            'prices' => ['CAD' => 4000],
        ]);

        new OfferServicesAtLocation(location: $scranton)->execute();

        $this->assertEquals(1, LocationService::query()->where('location_id', $scranton->id)->count());
        $this->assertEquals(['CAD' => 4000], LocationService::query()->where('location_id', $scranton->id)->first()->prices);
    }

    #[Test]
    public function it_never_offers_a_service_of_another_company(): void
    {
        $dunderMifflin = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $dunderMifflin->id]);
        $vance = Company::factory()->create();
        Service::factory()->create(['company_id' => $vance->id]);

        new OfferServicesAtLocation(location: $scranton)->execute();

        $this->assertEquals(0, LocationService::query()->count());
    }
}
