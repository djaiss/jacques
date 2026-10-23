<?php

declare(strict_types=1);

use App\Models\AppointmentService;
use App\Models\ClientTechnicalNote;
use App\Models\Service;

it('belongs to an appointment and a service', function () {
    $appointmentService = AppointmentService::factory()->create();

    expect($appointmentService->appointment()->exists())->toBeTrue();
    expect($appointmentService->service()->exists())->toBeTrue();
});

it('has many technical notes', function () {
    $note = ClientTechnicalNote::factory()->create();

    expect($note->appointmentService->technicalNotes()->exists())->toBeTrue();
});

it('copies the service at booking', function () {
    $appointmentService = AppointmentService::factory()->create();
    $service = $appointmentService->service;

    expect($appointmentService->name)->toBe($service->name);
    expect($appointmentService->price)->toBe(3500);
    expect($appointmentService->currency)->toBe('CAD');
    expect($appointmentService->duration)->toBe(30);
});

it('keeps its price once the catalogue changes', function () {
    $appointmentService = AppointmentService::factory()->create();

    $appointmentService->service->update(['prices' => ['CAD' => 4200], 'name' => ['en' => "Men's Haircut and Beard"]]);

    $appointmentService->refresh();
    expect($appointmentService->price)->toBe(3500);
    expect($appointmentService->name)->not->toBe(['en' => "Men's Haircut and Beard"]);
});

it('stays once its service is deleted', function () {
    $appointmentService = AppointmentService::factory()->create(['name' => ['en' => "Men's Haircut"]]);

    Service::query()->whereKey($appointmentService->service_id)->delete();

    $appointmentService->refresh();
    expect($appointmentService->service_id)->toBeNull();
    expect($appointmentService->localizedName('en'))->toBe("Men's Haircut");
});

it('reads its name in the language of the interface', function () {
    $appointmentService = AppointmentService::factory()->create(['name' => ['en' => "Men's Haircut", 'fr' => 'Coupe homme']]);
    $appointmentService->appointment->company->update(['languages' => ['en', 'fr']]);

    expect($appointmentService->refresh()->localizedName('fr_FR'))->toBe('Coupe homme');
    expect($appointmentService->localizedName('de'))->toBe("Men's Haircut");
});
