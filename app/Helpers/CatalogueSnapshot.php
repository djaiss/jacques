<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;

class CatalogueSnapshot
{
    /** @return array{categories: list<array<string, mixed>>, services: list<array<string, mixed>>} */
    public static function ofService(Service $service): array
    {
        return [
            'categories' => [],
            'services' => [self::service($service)],
        ];
    }

    /** @return array{categories: list<array<string, mixed>>, services: list<array<string, mixed>>} */
    public static function ofCategory(ServiceCategory $category): array
    {
        return [
            'categories' => [self::category($category)],
            'services' => [],
        ];
    }

    /** @return array{categories: list<array<string, mixed>>, services: list<array<string, mixed>>} */
    public static function ofCompany(Company $company): array
    {
        $categories = ServiceCategory::query()
            ->where('company_id', $company->id)
            ->with('services.steps')
            ->orderBy('position')
            ->get();

        $services = Service::query()
            ->where('company_id', $company->id)
            ->whereNull('service_category_id')
            ->with('steps')
            ->orderBy('position')
            ->get();

        return [
            'categories' => $categories->map(self::category(...))->all(),
            'services' => $services->map(self::service(...))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private static function category(ServiceCategory $category): array
    {
        return [
            'name' => $category->name,
            'description' => $category->description,
            'position' => $category->position,
            'services' => $category->services->map(self::service(...))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private static function service(Service $service): array
    {
        return [
            'service_category_id' => $service->service_category_id,
            'name' => $service->name,
            'description' => $service->description,
            'prices' => $service->prices,
            'is_active' => $service->is_active,
            'position' => $service->position,
            'steps' => $service->steps->map(fn (ServiceStep $step): array => [
                'name' => $step->name,
                'duration' => $step->duration,
                'requires_staff' => $step->requires_staff,
            ])->all(),
        ];
    }
}
