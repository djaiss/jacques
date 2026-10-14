<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'legal_name' => $name.' Inc.',
            'website_url' => fake()->url(),
            'currency' => 'USD',
        ];
    }

    /**
     * Indicate that the company is still within its trial period.
     */
    public function onTrial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'trial_ends_at' => now()->addDays(30),
        ]);
    }
}
