<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultCatalogue;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultCatalogueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_categories_in_order(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultCatalogue(company: $company)->execute();

        $categories = ServiceCategory::query()
            ->where('company_id', $company->id)
            ->orderBy('position')
            ->get()
            ->map(fn (ServiceCategory $category): string => $category->name['en'])
            ->all();

        $this->assertEquals(
            ['Haircuts', 'Hair Coloring', 'Hair Styling', 'Hair Treatments', 'Barbering', 'Other Services'],
            $categories,
        );
    }

    #[Test]
    public function it_creates_the_services_in_order_with_their_prices_and_durations(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultCatalogue(company: $company)->execute();

        $services = Service::query()
            ->where('services.company_id', $company->id)
            ->join('service_categories', 'service_categories.id', '=', 'services.service_category_id')
            ->orderBy('service_categories.position')
            ->orderBy('services.position')
            ->select('services.*')
            ->with('steps')
            ->get()
            ->map(fn (Service $service): array => [$service->name['en'], $service->price, $service->duration, $service->is_active])
            ->all();

        $this->assertEquals([
            ["Men's Haircut", 3500, 30, true],
            ["Women's Haircut", 6500, 60, true],
            ["Kids' Haircut", 2500, 30, true],
            ['Full Color', 10000, 90, true],
            ['Root Touch-up', 8500, 90, true],
            ['Balayage', 18000, 150, true],
            ['Highlights', 14000, 105, true],
            ['Blowout', 4500, 45, true],
            ['Updo', 7500, 60, true],
            ['Hair Straightening', 10000, 90, true],
            ['Deep Conditioning', 4000, 35, true],
            ['Keratin Treatment', 15000, 120, true],
            ['Scalp Treatment', 5500, 45, true],
            ['Beard Trim', 2000, 20, true],
            ['Traditional Shave', 3500, 30, true],
            ['Hair & Beard', 5000, 50, true],
            ['Consultation', 0, 15, true],
        ], $services);
    }

    #[Test]
    public function it_splits_three_services_into_steps_with_processing_time(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultCatalogue(company: $company)->execute();

        $this->assertEquals(20, ServiceStep::query()->whereIn('service_id', Service::query()->where('company_id', $company->id)->select('id'))->count());
        $this->assertEquals([[30, 30], [30, 0]], $this->steps($company, 'Full Color'));
        $this->assertEquals([[45, 30], [30, 0]], $this->steps($company, 'Highlights'));
        $this->assertEquals([[10, 15], [10, 0]], $this->steps($company, 'Deep Conditioning'));
        $this->assertEquals([[30, 0]], $this->steps($company, "Men's Haircut"));
    }

    #[Test]
    public function it_names_every_category_and_service_in_every_interface_locale(): void
    {
        $company = Company::factory()->create(['languages' => ['en', 'pt']]);

        new CreateDefaultCatalogue(company: $company)->execute();

        $names = [
            ...ServiceCategory::query()->where('company_id', $company->id)->pluck('name')->all(),
            ...Service::query()->where('company_id', $company->id)->pluck('name')->all(),
        ];

        $this->assertCount(23, $names);

        foreach ($names as $name) {
            $this->assertEqualsCanonicalizing(['en', 'fr', 'de', 'es'], array_keys($name));
            $this->assertNotContains('', $name);
        }

        $haircut = Service::query()->where('company_id', $company->id)->where('name->en', "Women's Haircut")->firstOrFail();

        $this->assertEquals('Coupe femme', $haircut->name['fr']);
        $this->assertEquals('Damenhaarschnitt', $haircut->name['de']);
        $this->assertEquals('Corte de dama', $haircut->name['es']);
    }

    #[Test]
    public function it_records_when_the_catalogue_was_created(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultCatalogue(company: $company)->execute();

        $this->assertEqualsWithDelta(now()->timestamp, $company->refresh()->default_catalogue_created_at?->timestamp, 5);
    }

    #[Test]
    public function it_creates_nothing_the_second_time(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultCatalogue(company: $company)->execute();
        new CreateDefaultCatalogue(company: $company)->execute();

        $this->assertEquals(6, ServiceCategory::query()->where('company_id', $company->id)->count());
        $this->assertEquals(17, Service::query()->where('company_id', $company->id)->count());
    }

    #[Test]
    public function it_keeps_what_the_owner_deleted_or_edited_when_it_runs_again(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultCatalogue(company: $company)->execute();

        Service::query()->where('company_id', $company->id)->where('name->en', 'Balayage')->delete();
        Service::query()->where('company_id', $company->id)->where('name->en', 'Blowout')->update(['price' => 5000]);

        new CreateDefaultCatalogue(company: $company)->execute();

        $this->assertFalse(Service::query()->where('company_id', $company->id)->where('name->en', 'Balayage')->exists());
        $this->assertEquals(5000, Service::query()->where('company_id', $company->id)->where('name->en', 'Blowout')->value('price'));
        $this->assertEquals(16, Service::query()->where('company_id', $company->id)->count());
    }

    #[Test]
    public function it_offers_the_services_at_the_headquarters(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id, 'is_headquarters' => true]);
        $stamford = Location::factory()->create(['company_id' => $company->id, 'is_headquarters' => false]);

        new CreateDefaultCatalogue(company: $company)->execute();

        $this->assertEquals(17, LocationService::query()->where('location_id', $scranton->id)->whereNull('price')->where('is_active', true)->count());
        $this->assertEquals(0, LocationService::query()->where('location_id', $stamford->id)->count());
    }

    #[Test]
    public function it_offers_nothing_without_a_headquarters(): void
    {
        $company = Company::factory()->create();

        new CreateDefaultCatalogue(company: $company)->execute();

        $this->assertEquals(0, LocationService::query()->count());
    }

    #[Test]
    public function it_leaves_another_company_untouched(): void
    {
        $dunderMifflin = Company::factory()->create();
        $vance = Company::factory()->create();
        Location::factory()->create(['company_id' => $vance->id, 'is_headquarters' => true]);

        new CreateDefaultCatalogue(company: $dunderMifflin)->execute();

        $this->assertEquals(0, ServiceCategory::query()->where('company_id', $vance->id)->count());
        $this->assertEquals(0, Service::query()->where('company_id', $vance->id)->count());
        $this->assertEquals(0, LocationService::query()->count());
        $this->assertNull($vance->refresh()->default_catalogue_created_at);
    }

    /** @return list<array{int, int}> */
    private function steps(Company $company, string $name): array
    {
        return ServiceStep::query()
            ->whereIn('service_id', Service::query()->where('company_id', $company->id)->where('name->en', $name)->select('id'))
            ->orderBy('position')
            ->get()
            ->map(fn (ServiceStep $step): array => [$step->active_duration, $step->processing_duration])
            ->all();
    }
}
