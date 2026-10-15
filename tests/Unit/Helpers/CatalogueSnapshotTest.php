<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\CatalogueSnapshot;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogueSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_a_service_with_its_place_prices_and_steps(): void
    {
        $service = Service::factory()->create(['name' => ['en' => 'Beard trim'], 'prices' => ['CAD' => 2000], 'position' => 3]);

        $snapshot = CatalogueSnapshot::ofService($service);

        $this->assertEquals([], $snapshot['categories']);
        $this->assertEquals(['en' => 'Beard trim'], $snapshot['services'][0]['name']);
        $this->assertEquals(3, $snapshot['services'][0]['position']);
        $this->assertEquals([['name' => null, 'duration' => 30, 'requires_staff' => true]], $snapshot['services'][0]['steps']);
    }

    #[Test]
    public function it_keeps_the_whole_catalogue_of_a_company(): void
    {
        $company = Company::factory()->create();
        $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
        Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id]);
        Service::factory()->create(['company_id' => $company->id]);
        Service::factory()->create();

        $snapshot = CatalogueSnapshot::ofCompany($company);

        $this->assertCount(1, $snapshot['categories']);
        $this->assertCount(1, $snapshot['categories'][0]['services']);
        $this->assertCount(1, $snapshot['services']);
    }
}
