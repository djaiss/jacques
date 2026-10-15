<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create();
        $service = Service::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($service->company()->exists());
        $this->assertEquals($company->id, $service->company->id);
    }

    #[Test]
    public function it_belongs_to_a_category(): void
    {
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->create(['company_id' => $category->company_id, 'service_category_id' => $category->id]);

        $this->assertTrue($service->category()->exists());
        $this->assertEquals($category->id, $service->category->id);
    }

    #[Test]
    public function it_has_no_category_by_default(): void
    {
        $service = Service::factory()->create();

        $this->assertNull($service->category);
    }

    #[Test]
    public function it_has_many_steps_in_the_order_they_are_performed(): void
    {
        $service = Service::factory()->create();
        $second = ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2]);

        $this->assertTrue($service->steps()->exists());
        $this->assertEquals([1, 2], $service->steps->pluck('position')->all());
        $this->assertEquals($second->id, $service->steps->last()->id);
    }

    #[Test]
    public function it_has_many_location_services(): void
    {
        $locationService = LocationService::factory()->create();

        $this->assertTrue($locationService->service->locationServices()->exists());
    }

    #[Test]
    public function it_belongs_to_many_locations(): void
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create(['company_id' => $location->company_id]);
        $service->locations()->attach($location->id, ['prices' => json_encode(['CAD' => 4000])]);

        $this->assertTrue($service->locations()->exists());
        $this->assertEquals(['CAD' => 4000], json_decode($service->locations->first()->pivot->prices, true));
    }

    #[Test]
    public function it_lasts_the_active_and_processing_time_of_its_steps(): void
    {
        $service = Service::factory()->create();
        $service->steps()->delete();
        ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 1, 'active_duration' => 20, 'processing_duration' => 30]);
        ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2, 'active_duration' => 15, 'processing_duration' => 0]);

        $this->assertEquals(65, $service->refresh()->duration);
    }

    #[Test]
    public function it_casts_the_prices_and_the_active_flag(): void
    {
        $service = Service::factory()->inactive()->create(['prices' => ['CAD' => 3500, 'USD' => 2600]]);

        $this->assertSame(['CAD' => 3500, 'USD' => 2600], $service->refresh()->prices);
        $this->assertFalse($service->is_active);
    }

    #[Test]
    public function it_reads_its_name_in_the_language_of_the_interface(): void
    {
        $company = Company::factory()->create(['languages' => ['fr', 'en']]);
        $haircut = Service::factory()->create([
            'company_id' => $company->id,
            'name' => ['fr' => 'Coupe femme', 'en' => "Women's haircut"],
        ]);

        $this->assertEquals("Women's haircut", $haircut->localizedName('en'));
        $this->assertEquals('Coupe femme', $haircut->localizedName('de_DE'));
    }

    #[Test]
    public function it_lists_the_currencies_it_has_no_price_in(): void
    {
        $company = Company::factory()->create(['currencies' => ['CAD', 'USD', 'EUR']]);
        $haircut = Service::factory()->create([
            'company_id' => $company->id,
            'prices' => ['CAD' => 6500, 'GBP' => 3800],
        ]);

        $this->assertEquals(['USD', 'EUR'], $haircut->missingCurrencies());
    }

    #[Test]
    public function it_lists_the_languages_its_name_is_missing_in(): void
    {
        $company = Company::factory()->create(['languages' => ['en', 'fr', 'de']]);
        $haircut = Service::factory()->create([
            'company_id' => $company->id,
            'name' => ['en' => "Women's haircut", 'es' => 'Algo'],
        ]);

        $this->assertEquals(['fr', 'de'], $haircut->missingLanguages());
    }
}
