<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffAbsence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffAbsence>
 */
class StaffAbsenceFactory extends Factory
{
    protected $model = StaffAbsence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now('UTC')->addDays(7)->startOfDay();

        return [
            'staff_id' => Staff::factory(),
            'location_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDay(),
            'reason' => fake()->randomElement(['Holiday', 'Sick leave', 'Training']),
        ];
    }
}
