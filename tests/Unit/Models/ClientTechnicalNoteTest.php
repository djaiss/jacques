<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Client;
use App\Models\ClientTechnicalNote;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

it('belongs to a client, a service of an appointment and its author', function () {
    $note = ClientTechnicalNote::factory()->create();

    expect($note->client()->exists())->toBeTrue();
    expect($note->appointmentService()->exists())->toBeTrue();
    expect($note->staff()->exists())->toBeTrue();
});

it('belongs to the client of its appointment', function () {
    $note = ClientTechnicalNote::factory()->create();

    expect($note->client_id)->toBe($note->appointmentService->appointment->client_id);
});

it('encrypts its text', function () {
    $note = ClientTechnicalNote::factory()->create(['body' => 'Color formula: 6.1 + 7.0 (1:1)']);

    $stored = DB::table('client_technical_notes')->where('id', $note->id)->value('body');

    expect($stored)->not->toContain('6.1');
    expect($note->refresh()->body)->toBe('Color formula: 6.1 + 7.0 (1:1)');
});

it('keeps the name of its author once the author is deleted', function () {
    $note = ClientTechnicalNote::factory()->create(['author_name' => 'Kelly Kapoor']);

    Staff::query()->whereKey($note->staff_id)->delete();

    $note->refresh();
    expect($note->staff_id)->toBeNull();
    expect($note->author_name)->toBe('Kelly Kapoor');
});

it('lists the technical notes of a client newest first across appointments', function () {
    $pam = Client::factory()->create(['first_name' => 'Pam']);
    $this->travelTo('2026-08-01 10:00:00');
    $firstColouring = AppointmentService::factory()->create([
        'appointment_id' => Appointment::factory()->create(['company_id' => $pam->company_id, 'client_id' => $pam->id])->id,
    ]);
    $older = ClientTechnicalNote::factory()->create(['appointment_service_id' => $firstColouring->id]);
    $this->travelTo('2026-10-01 10:00:00');
    $secondColouring = AppointmentService::factory()->create([
        'appointment_id' => Appointment::factory()->create(['company_id' => $pam->company_id, 'client_id' => $pam->id])->id,
    ]);
    $newer = ClientTechnicalNote::factory()->create(['appointment_service_id' => $secondColouring->id]);

    expect($pam->technicalNotes()->pluck('id')->all())->toBe([$newer->id, $older->id]);
});
