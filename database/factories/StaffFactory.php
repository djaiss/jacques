<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->companyEmail(),
        ];
    }

    /**
     * A staff member who has left the company.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * A staff member clients cannot book.
     */
    public function unbookable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_bookable' => false,
        ]);
    }
}
