<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'service_category_id' => null,
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'price' => 3500,
            'is_active' => true,
            'position' => 1,
        ];
    }

    #[Override]
    public function configure(): static
    {
        return $this->afterCreating(function (Service $service): void {
            ServiceStep::query()->create([
                'service_id' => $service->id,
                'position' => 1,
                'active_duration' => 30,
                'processing_duration' => 0,
            ]);
        });
    }

    /**
     * A service the company no longer offers.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
