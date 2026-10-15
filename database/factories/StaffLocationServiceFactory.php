<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LocationService;
use App\Models\Staff;
use App\Models\StaffLocationService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffLocationService>
 */
class StaffLocationServiceFactory extends Factory
{
    protected $model = StaffLocationService::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'location_service_id' => LocationService::factory(),
        ];
    }
}
