<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StaffInvitation>
 */
class StaffInvitationFactory extends Factory
{
    protected $model = StaffInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'role_id' => Role::factory(),
            'author_id' => User::factory(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'token_hash' => hash('sha256', Str::random(64)),
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'token_hash' => hash('sha256', Str::random(64)),
            'sent_at' => now()->subDays(8),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'sent_at' => now()->subDay(),
            'expires_at' => now()->addDays(6),
            'accepted_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'sent_at' => now()->subDay(),
            'expires_at' => now()->addDays(6),
            'revoked_at' => now(),
        ]);
    }
}
