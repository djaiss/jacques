<?php

declare(strict_types=1);

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Location;
use App\Models\Staff;
use Carbon\Carbon;

it('belongs to a company, a location, a client and a staff member', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->company()->exists())->toBeTrue();
    expect($appointment->location()->exists())->toBeTrue();
    expect($appointment->client()->exists())->toBeTrue();
    expect($appointment->staff()->exists())->toBeTrue();
});

it('keeps the company of its client, staff member and location', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->client->company_id)->toBe($appointment->company_id);
    expect($appointment->staff->company_id)->toBe($appointment->company_id);
    expect($appointment->location->company_id)->toBe($appointment->company_id);
});

it('has many services in their order', function () {
    $appointment = Appointment::factory()->create();
    $second = AppointmentService::factory()->create(['appointment_id' => $appointment->id, 'position' => 2]);
    $first = AppointmentService::factory()->create(['appointment_id' => $appointment->id, 'position' => 1]);

    expect($appointment->services()->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('casts its attributes', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatusEnum::NoShow])->refresh();

    expect($appointment->starts_at)->toBeInstanceOf(Carbon::class);
    expect($appointment->ends_at)->toBeInstanceOf(Carbon::class);
    expect($appointment->status)->toBe(AppointmentStatusEnum::NoShow);
});

it('stays once its staff member and location are deleted', function () {
    $appointment = Appointment::factory()->create();

    Staff::query()->whereKey($appointment->staff_id)->delete();
    Location::query()->whereKey($appointment->location_id)->delete();

    $appointment->refresh();
    expect($appointment->staff_id)->toBeNull();
    expect($appointment->location_id)->toBeNull();
});
