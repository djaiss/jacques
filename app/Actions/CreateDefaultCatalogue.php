<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Company;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copy the catalogue every new salon starts with into a company, once: its
 * categories, services and steps, named in every interface locale, then offer
 * them at the headquarters when the company already has one.
 */
class CreateDefaultCatalogue
{
    public function __construct(
        private Company $company,
    ) {}

    public function execute(): void
    {
        DB::transaction(function (): void {
            $this->company = Company::query()
                ->lockForUpdate()
                ->findOrFail($this->company->id);

            if ($this->company->default_catalogue_created_at !== null) {
                return;
            }

            $this->createCatalogue();

            $this->company->default_catalogue_created_at = now();
            $this->company->save();

            $this->offerAtHeadquarters();
        });
    }

    private function createCatalogue(): void
    {
        $catalogues = [];

        foreach (array_keys(config('jacques.locales')) as $locale) {
            $catalogues[Str::before($locale, '_')] = $this->catalogue($locale);
        }

        $source = $catalogues['en'];

        foreach ($source as $categoryIndex => $category) {
            $serviceCategory = ServiceCategory::query()->create([
                'company_id' => $this->company->id,
                'name' => array_map(fn (array $catalogue): string => $catalogue[$categoryIndex]['name'], $catalogues),
                'description' => null,
                'position' => $categoryIndex + 1,
            ]);

            foreach ($category['services'] as $serviceIndex => $service) {
                $createdService = Service::query()->create([
                    'company_id' => $this->company->id,
                    'service_category_id' => $serviceCategory->id,
                    'name' => array_map(fn (array $catalogue): string => $catalogue[$categoryIndex]['services'][$serviceIndex]['name'], $catalogues),
                    'description' => null,
                    'prices' => ['CAD' => $service['price']],
                    'is_active' => true,
                    'position' => $serviceIndex + 1,
                ]);

                foreach ($service['steps'] as $stepIndex => $step) {
                    ServiceStep::query()->create([
                        'service_id' => $createdService->id,
                        'position' => $stepIndex + 1,
                        'active_duration' => $step['active_duration'],
                        'processing_duration' => $step['processing_duration'],
                    ]);
                }
            }
        }
    }

    private function offerAtHeadquarters(): void
    {
        $headquarters = Location::query()
            ->where('company_id', $this->company->id)
            ->where('is_headquarters', true)
            ->first();

        if ($headquarters === null) {
            return;
        }

        new OfferServicesAtLocation(location: $headquarters)->execute();
    }

    /** @return list<array{name: string, services: list<array{name: string, price: int, steps: list<array{active_duration: int, processing_duration: int}>}>}> */
    private function catalogue(string $locale): array
    {
        return [
            [
                'name' => __('Haircuts', locale: $locale),
                'services' => [
                    ['name' => __("Men's Haircut", locale: $locale), 'price' => 3500, 'steps' => [['active_duration' => 30, 'processing_duration' => 0]]],
                    ['name' => __("Women's Haircut", locale: $locale), 'price' => 6500, 'steps' => [['active_duration' => 60, 'processing_duration' => 0]]],
                    ['name' => __("Kids' Haircut", locale: $locale), 'price' => 2500, 'steps' => [['active_duration' => 30, 'processing_duration' => 0]]],
                ],
            ],
            [
                'name' => __('Hair Coloring', locale: $locale),
                'services' => [
                    ['name' => __('Full Color', locale: $locale), 'price' => 10000, 'steps' => [['active_duration' => 30, 'processing_duration' => 30], ['active_duration' => 30, 'processing_duration' => 0]]],
                    ['name' => __('Root Touch-up', locale: $locale), 'price' => 8500, 'steps' => [['active_duration' => 90, 'processing_duration' => 0]]],
                    ['name' => __('Balayage', locale: $locale), 'price' => 18000, 'steps' => [['active_duration' => 150, 'processing_duration' => 0]]],
                    ['name' => __('Highlights', locale: $locale), 'price' => 14000, 'steps' => [['active_duration' => 45, 'processing_duration' => 30], ['active_duration' => 30, 'processing_duration' => 0]]],
                ],
            ],
            [
                'name' => __('Hair Styling', locale: $locale),
                'services' => [
                    ['name' => __('Blowout', locale: $locale), 'price' => 4500, 'steps' => [['active_duration' => 45, 'processing_duration' => 0]]],
                    ['name' => __('Updo', locale: $locale), 'price' => 7500, 'steps' => [['active_duration' => 60, 'processing_duration' => 0]]],
                    ['name' => __('Hair Straightening', locale: $locale), 'price' => 10000, 'steps' => [['active_duration' => 90, 'processing_duration' => 0]]],
                ],
            ],
            [
                'name' => __('Hair Treatments', locale: $locale),
                'services' => [
                    ['name' => __('Deep Conditioning', locale: $locale), 'price' => 4000, 'steps' => [['active_duration' => 10, 'processing_duration' => 15], ['active_duration' => 10, 'processing_duration' => 0]]],
                    ['name' => __('Keratin Treatment', locale: $locale), 'price' => 15000, 'steps' => [['active_duration' => 120, 'processing_duration' => 0]]],
                    ['name' => __('Scalp Treatment', locale: $locale), 'price' => 5500, 'steps' => [['active_duration' => 45, 'processing_duration' => 0]]],
                ],
            ],
            [
                'name' => __('Barbering', locale: $locale),
                'services' => [
                    ['name' => __('Beard Trim', locale: $locale), 'price' => 2000, 'steps' => [['active_duration' => 20, 'processing_duration' => 0]]],
                    ['name' => __('Traditional Shave', locale: $locale), 'price' => 3500, 'steps' => [['active_duration' => 30, 'processing_duration' => 0]]],
                    ['name' => __('Hair & Beard', locale: $locale), 'price' => 5000, 'steps' => [['active_duration' => 50, 'processing_duration' => 0]]],
                ],
            ],
            [
                'name' => __('Other Services', locale: $locale),
                'services' => [
                    ['name' => __('Consultation', locale: $locale), 'price' => 0, 'steps' => [['active_duration' => 15, 'processing_duration' => 0]]],
                ],
            ],
        ];
    }
}
