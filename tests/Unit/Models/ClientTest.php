<?php

declare(strict_types=1);

use App\Enums\GenderEnum;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientConsent;
use App\Models\ClientNote;
use App\Models\ClientTechnicalNote;
use App\Models\Company;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('belongs to a company', function () {
    $client = Client::factory()->create();

    expect($client->company()->exists())->toBeTrue();
});

it('belongs to a preferred staff member', function () {
    $company = Company::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $company->id, 'first_name' => 'Jim']);
    $michael = Client::factory()->create(['company_id' => $company->id, 'preferred_staff_id' => $jim->id]);

    expect($michael->preferredStaff()->exists())->toBeTrue();
});

it('has many appointments', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->client->appointments()->exists())->toBeTrue();
});

it('has many notes', function () {
    $note = ClientNote::factory()->create();

    expect($note->client->notes()->exists())->toBeTrue();
});

it('has many technical notes', function () {
    $note = ClientTechnicalNote::factory()->create();

    expect($note->client->technicalNotes()->exists())->toBeTrue();
});

it('has many consents', function () {
    $client = Client::factory()->create();
    ClientConsent::factory()->create(['client_id' => $client->id]);

    expect($client->consents()->exists())->toBeTrue();
});

it('casts its attributes', function () {
    $client = Client::factory()->create([
        'gender' => GenderEnum::Male,
        'birth_date' => '1964-03-15',
        'special_instructions' => 'No scented product',
        'anonymized_at' => now(),
    ])->refresh();

    expect($client->gender)->toBe(GenderEnum::Male);
    expect($client->birth_date)->toBeInstanceOf(Carbon::class);
    expect($client->special_instructions)->toBe('No scented product');
    expect($client->receives_sms_reminders)->toBeTrue();
    expect($client->is_online_booking_blocked)->toBeFalse();
    expect($client->anonymized_at)->toBeInstanceOf(Carbon::class);
});

it('encrypts the special instructions', function () {
    $client = Client::factory()->create(['special_instructions' => 'No scented product']);

    $stored = DB::table('clients')->where('id', $client->id)->value('special_instructions');

    expect($stored)->not->toContain('scented');
});

it('refuses two clients of a company with the same phone', function () {
    $company = Company::factory()->create();
    Client::factory()->create(['company_id' => $company->id, 'phone' => '+15705550100', 'first_name' => 'Michael']);

    Client::factory()->create(['company_id' => $company->id, 'phone' => '+15705550100', 'first_name' => 'Dwight']);
})->throws(UniqueConstraintViolationException::class);

it('accepts the same phone in two companies', function () {
    Client::factory()->create(['phone' => '+15705550100']);
    Client::factory()->create(['phone' => '+15705550100']);

    $this->assertDatabaseCount('clients', 2);
});

it('accepts several clients of a company without phone', function () {
    $company = Company::factory()->create();
    Client::factory()->count(2)->create(['company_id' => $company->id, 'phone' => null]);

    $this->assertDatabaseCount('clients', 2);
});

it('keeps its appointments once its personal fields are emptied', function () {
    $client = Client::factory()->create(['email' => 'michael@dundermifflin.com', 'special_instructions' => 'No scented product']);
    Appointment::factory()->create(['company_id' => $client->company_id, 'client_id' => $client->id]);

    $client->update([
        'first_name' => null,
        'last_name' => null,
        'phone' => null,
        'email' => null,
        'birth_date' => null,
        'special_instructions' => null,
        'anonymized_at' => now(),
    ]);

    $this->assertDatabaseHas('clients', ['id' => $client->id, 'first_name' => null, 'phone' => null, 'email' => null]);
    expect($client->appointments()->count())->toBe(1);
});
