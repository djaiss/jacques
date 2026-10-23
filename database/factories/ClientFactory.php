<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->unique()->numerify('+1570555####'),
            'email' => null,
            'gender' => null,
            'birth_date' => null,
            'preferred_staff_id' => null,
            'special_instructions' => null,
            'receives_sms_reminders' => true,
            'is_online_booking_blocked' => false,
        ];
    }
}
