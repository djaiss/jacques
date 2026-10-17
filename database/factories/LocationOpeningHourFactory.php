<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationOpeningHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationOpeningHour>
 */
class LocationOpeningHourFactory extends Factory
{
    protected $model = LocationOpeningHour::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ];
    }
}
