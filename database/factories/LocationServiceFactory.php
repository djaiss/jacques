<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationService>
 */
class LocationServiceFactory extends Factory
{
    protected $model = LocationService::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'service_id' => fn (array $attributes): Service => Service::factory()->create([
                'company_id' => Location::query()->findOrFail($attributes['location_id'])->company_id,
            ]),
            'prices' => null,
            'is_active' => true,
        ];
    }
}
