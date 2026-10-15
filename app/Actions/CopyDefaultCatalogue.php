<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\DefaultCatalogue;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;

/**
 * Write the example catalogue into a company, after whatever it already holds:
 * its categories, services and steps, named in every interface locale and
 * priced in every offered currency. Callers decide when it may run.
 */
class CopyDefaultCatalogue
{
    public function __construct(
        private readonly Company $company,
    ) {}

    public function execute(): void
    {
        $lastPosition = (int) ServiceCategory::query()
            ->where('company_id', $this->company->id)
            ->max('position');

        foreach (DefaultCatalogue::categories() as $categoryIndex => $category) {
            $serviceCategory = ServiceCategory::query()->create([
                'company_id' => $this->company->id,
                'name' => $category['name'],
                'description' => null,
                'position' => $lastPosition + $categoryIndex + 1,
            ]);

            foreach ($category['services'] as $serviceIndex => $service) {
                $createdService = Service::query()->create([
                    'company_id' => $this->company->id,
                    'service_category_id' => $serviceCategory->id,
                    'name' => $service['name'],
                    'description' => null,
                    'prices' => array_fill_keys(config('jacques.currencies'), $service['price']),
                    'is_active' => true,
                    'position' => $serviceIndex + 1,
                ]);

                foreach ($service['steps'] as $stepIndex => $step) {
                    ServiceStep::query()->create([
                        'service_id' => $createdService->id,
                        'position' => $stepIndex + 1,
                        'name' => $step['name'],
                        'duration' => $step['duration'],
                        'requires_staff' => $step['requires_staff'],
                    ]);
                }
            }
        }
    }
}
