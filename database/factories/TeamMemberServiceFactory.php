<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LocationService;
use App\Models\TeamMemberService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMemberService>
 */
class TeamMemberServiceFactory extends Factory
{
    protected $model = TeamMemberService::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'location_service_id' => LocationService::factory(),
        ];
    }
}
