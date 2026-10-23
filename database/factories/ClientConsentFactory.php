<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConsentChannelEnum;
use App\Models\Client;
use App\Models\ClientConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientConsent>
 */
class ClientConsentFactory extends Factory
{
    protected $model = ClientConsent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'channel' => ConsentChannelEnum::Sms,
            'is_granted' => true,
            'staff_id' => null,
        ];
    }
}
