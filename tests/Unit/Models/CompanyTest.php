<?php

declare(strict_types=1);

use App\Enums\OnboardingStepEnum;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;

it('has many users', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    User::factory()->count(2)->create(['company_id' => $company->id]);

    expect($company->users()->exists())->toBeTrue();
    expect($company->users()->get())->toHaveCount(2);
});

it('has many staff members in the order of their position', function () {
    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $michael = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);

    expect($company->staff()->exists())->toBeTrue();
    expect($company->staff->pluck('id')->all())->toEqual([$michael->id, $dwight->id]);
});

it('has many locations', function () {
    $company = Company::factory()->create();
    Location::factory()->count(2)->create(['company_id' => $company->id]);

    expect($company->locations()->exists())->toBeTrue();
    expect($company->locations()->get())->toHaveCount(2);
});

it('has many service categories in the order of their position', function () {
    $company = Company::factory()->create();
    $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $cuts = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);

    expect($company->serviceCategories()->exists())->toBeTrue();
    expect($company->serviceCategories->pluck('id')->all())->toEqual([$cuts->id, $colours->id]);
});

it('has many services', function () {
    $company = Company::factory()->create();
    Service::factory()->count(2)->create(['company_id' => $company->id]);

    expect($company->services()->exists())->toBeTrue();
    expect($company->services()->get())->toHaveCount(2);
});

it('has many clients', function () {
    $company = Company::factory()->create();
    Client::factory()->count(2)->create(['company_id' => $company->id]);

    expect($company->clients()->exists())->toBeTrue();
    expect($company->clients()->get())->toHaveCount(2);
});

it('has many appointments', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->company->appointments()->exists())->toBeTrue();
});

it('knows whether it is on trial', function () {
    $onTrial = Company::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    $expired = Company::factory()->create(['trial_ends_at' => now()->subDay()]);
    $never = Company::factory()->create(['trial_ends_at' => null]);

    expect($onTrial->isOnTrial())->toBeTrue();
    expect($expired->isOnTrial())->toBeFalse();
    expect($never->isOnTrial())->toBeFalse();
});

it('does not owe the welcome screen by default', function () {
    $company = Company::factory()->create();
    $pending = Company::factory()->welcomeScreenPending()->create();

    expect($company->refresh()->is_welcome_screen_pending)->toBeFalse();
    expect($pending->refresh()->is_welcome_screen_pending)->toBeTrue();
});

it('does not owe the onboarding by default', function () {
    $company = Company::factory()->create();
    $pending = Company::factory()->onboardingPending()->create();

    expect($company->refresh()->is_onboarding_pending)->toBeFalse();
    expect($pending->refresh()->is_onboarding_pending)->toBeTrue();
    expect($pending->onboarding_step)->toEqual(OnboardingStepEnum::Location);
});

it('keeps its languages in the order they were chosen', function () {
    $company = Company::factory()->create([
        'languages' => ['fr', 'en'],
    ]);

    expect($company->refresh()->languages)->toEqual(['fr', 'en']);
});

it('keeps its currencies in the order they were chosen', function () {
    $company = Company::factory()->create([
        'currencies' => ['USD', 'CAD'],
    ]);

    expect($company->refresh()->currencies)->toEqual(['USD', 'CAD']);
});

it('has not received the default catalogue by default', function () {
    $company = Company::factory()->create();

    expect($company->refresh()->default_catalogue_created_at)->toBeNull();
});

it('knows when it received the default catalogue', function () {
    $company = Company::factory()->create([
        'default_catalogue_created_at' => '2026-10-14 09:00:00',
    ]);

    expect($company->refresh()->default_catalogue_created_at)->toBeInstanceOf(Carbon::class);
});
