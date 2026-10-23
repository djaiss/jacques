<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Location;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now('UTC')->addDays(7)->setTime(10, 0);

        return [
            'company_id' => Company::factory(),
            'location_id' => fn (array $attributes): int => Location::factory()->create(['company_id' => $attributes['company_id']])->id,
            'client_id' => fn (array $attributes): int => Client::factory()->create(['company_id' => $attributes['company_id']])->id,
            'staff_id' => fn (array $attributes): int => Staff::factory()->create(['company_id' => $attributes['company_id']])->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'status' => AppointmentStatusEnum::Scheduled,
        ];
    }
}
