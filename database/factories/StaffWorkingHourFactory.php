<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffWorkingHour>
 */
class StaffWorkingHourFactory extends Factory
{
    protected $model = StaffWorkingHour::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'location_id' => Location::factory(),
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ];
    }
}
