<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentService>
 */
class AppointmentServiceFactory extends Factory
{
    protected $model = AppointmentService::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'service_id' => fn (array $attributes): int => Service::factory()->create([
                'company_id' => Appointment::query()->findOrFail($attributes['appointment_id'])->company_id,
            ])->id,
            'name' => fn (array $attributes): array => Service::query()->findOrFail($attributes['service_id'])->name,
            'price' => fn (array $attributes): int => (int) collect(Service::query()->findOrFail($attributes['service_id'])->prices)->first(),
            'currency' => fn (array $attributes): string => (string) collect(Service::query()->findOrFail($attributes['service_id'])->prices)->keys()->first(),
            'duration' => fn (array $attributes): int => Service::query()->with('steps')->findOrFail($attributes['service_id'])->duration,
            'position' => 1,
        ];
    }
}
