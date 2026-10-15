<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceStep>
 */
class ServiceStepFactory extends Factory
{
    protected $model = ServiceStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'position' => 2,
            'active_duration' => 20,
            'processing_duration' => 30,
        ];
    }
}
