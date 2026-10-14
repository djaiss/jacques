<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->city().' location',
            'country' => fake()->countryCode(),
            'region' => fake()->randomElement(['Pennsylvania', 'New York', 'Connecticut']),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->numerify('Suite ###'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'timezone' => 'UTC',
        ];
    }

    /**
     * A location the company has closed.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'archived_at' => now(),
            'is_headquarters' => false,
        ]);
    }

    /**
     * The headquarters of the company.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_headquarters' => true,
        ]);
    }
}
