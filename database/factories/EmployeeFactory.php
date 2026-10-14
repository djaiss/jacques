<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_number' => fake()->unique()->numerify('EMP-####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'work_email' => fake()->unique()->companyEmail(),
        ];
    }
}
