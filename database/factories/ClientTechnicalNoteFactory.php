<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppointmentService;
use App\Models\ClientTechnicalNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientTechnicalNote>
 */
class ClientTechnicalNoteFactory extends Factory
{
    protected $model = ClientTechnicalNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_service_id' => AppointmentService::factory(),
            'client_id' => fn (array $attributes): int => AppointmentService::query()->findOrFail($attributes['appointment_service_id'])->appointment->client_id,
            'staff_id' => fn (array $attributes): ?int => AppointmentService::query()->findOrFail($attributes['appointment_service_id'])->appointment->staff_id,
            'author_name' => fake()->name(),
            'body' => 'Color formula: 6.1 + 7.0 (1:1). Developer: 20 vol. Processing time: 35 min.',
        ];
    }
}
