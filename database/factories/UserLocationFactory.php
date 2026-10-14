<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLocation>
 */
class UserLocationFactory extends Factory
{
    protected $model = UserLocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'location_id' => Location::factory(),
        ];
    }
}
