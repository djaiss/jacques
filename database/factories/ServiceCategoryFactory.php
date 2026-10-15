<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => ['en' => fake()->unique()->words(2, true)],
            'description' => ['en' => fake()->sentence()],
            'position' => 1,
        ];
    }
}
