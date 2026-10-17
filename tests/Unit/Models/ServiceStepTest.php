<?php

declare(strict_types=1);

use App\Models\Service;
use App\Models\ServiceStep;

it('belongs to a service', function () {
    $service = Service::factory()->create();
    $step = ServiceStep::factory()->create(['service_id' => $service->id]);

    expect($step->service()->exists())->toBeTrue();
    expect($step->service->id)->toEqual($service->id);
});

it('requires a team member unless told otherwise', function () {
    $service = Service::factory()->create();

    expect($service->steps->first()->requires_staff)->toBeTrue();
    expect($service->steps->first()->duration)->toBe(30);
});

it('casts its name per language', function () {
    $step = ServiceStep::factory()->create(['name' => ['en' => 'Processing', 'fr' => 'Temps de pose']]);

    expect($step->refresh()->name)->toBe(['en' => 'Processing', 'fr' => 'Temps de pose']);
});
