<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientNote;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientNote>
 */
class ClientNoteFactory extends Factory
{
    protected $model = ClientNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'staff_id' => fn (array $attributes): int => Staff::factory()->create([
                'company_id' => Client::query()->findOrFail($attributes['client_id'])->company_id,
            ])->id,
            'author_name' => fn (array $attributes): string => Staff::query()->findOrFail($attributes['staff_id'])->name,
            'body' => fake()->sentence(),
        ];
    }
}
