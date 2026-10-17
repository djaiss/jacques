<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationStaff>
 */
class LocationStaffFactory extends Factory
{
    protected $model = LocationStaff::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'staff_id' => Staff::factory(),
        ];
    }

    /**
     * An assignment that is not in effect.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * An assignment whose staff member works hours of their own.
     */
    public function withCustomHours(): static
    {
        return $this->state(fn (array $attributes): array => [
            'follows_opening_hours' => false,
        ]);
    }
}
