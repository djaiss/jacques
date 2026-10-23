<?php

declare(strict_types=1);

use App\Enums\ConsentChannelEnum;
use App\Models\Client;
use App\Models\ClientConsent;
use App\Models\Staff;

it('belongs to a client', function () {
    $consent = ClientConsent::factory()->create();

    expect($consent->client()->exists())->toBeTrue();
});

it('belongs to the staff member who recorded it', function () {
    $consent = ClientConsent::factory()->create(['staff_id' => Staff::factory()->create()->id]);

    expect($consent->staff()->exists())->toBeTrue();
});

it('casts its attributes', function () {
    $consent = ClientConsent::factory()->create(['channel' => ConsentChannelEnum::Email, 'is_granted' => false])->refresh();

    expect($consent->channel)->toBe(ConsentChannelEnum::Email);
    expect($consent->is_granted)->toBeFalse();
});

it('keeps every change of the consent in order', function () {
    $michael = Client::factory()->create();
    $this->travelTo('2026-10-01 10:00:00');
    ClientConsent::factory()->create(['client_id' => $michael->id, 'is_granted' => true]);
    $this->travelTo('2026-10-20 10:00:00');
    ClientConsent::factory()->create(['client_id' => $michael->id, 'is_granted' => false]);

    $consents = $michael->consents()->get();

    expect($consents)->toHaveCount(2);
    expect($consents->first()->is_granted)->toBeTrue();
    expect($consents->last()->is_granted)->toBeFalse();
});
