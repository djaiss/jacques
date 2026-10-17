<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\SetUpSalonTest;

use App\Actions\CreateDefaultCatalogue;
use App\Actions\CreateLocation;
use App\Actions\SetUpSalon;
use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('renames the company and creates its first location', function () {
    Queue::fake();

    $user = owner();

    $company = new SetUpSalon(
        author: $user,
        name: 'Dunder Mifflin Scranton',
        country: 'US',
        timezone: 'America/New_York',
        languages: ['en'],
        currencies: ['CAD'],
        region: 'Pennsylvania',
        city: 'Scranton',
        postalCode: '18503',
        addressLine1: '1725 Slough Avenue',
        phone: '+1 570 555 0100',
    )->execute();

    expect($company)->toBeInstanceOf(Company::class);
    expect($company->name)->toEqual('Dunder Mifflin Scranton');
    expect($company->legal_name)->toEqual('Dunder Mifflin Inc.');
    expect($company->onboarding_step)->toEqual(OnboardingStepEnum::Services);
    $this->assertDatabaseHas('locations', [
        'company_id' => $company->id,
        'name' => 'Dunder Mifflin Scranton',
        'country' => 'US',
        'region' => 'Pennsylvania',
        'city' => 'Scranton',
        'postal_code' => '18503',
        'address_line_1' => '1725 Slough Avenue',
        'phone' => '+1 570 555 0100',
        'timezone' => 'America/New_York',
        'is_headquarters' => true,
    ]);
});

it('saves the languages of the salon', function () {
    Queue::fake();

    $user = owner();

    $company = new SetUpSalon(
        author: $user,
        name: 'Dunder Mifflin Montreal',
        country: 'CA',
        timezone: 'America/Toronto',
        languages: ['fr', 'en'],
        currencies: ['CAD'],
    )->execute();

    expect($company->languages)->toEqual(['fr', 'en']);
});

it('saves the currencies of the salon', function () {
    Queue::fake();

    $user = owner();

    $company = new SetUpSalon(
        author: $user,
        name: 'Dunder Mifflin Buffalo',
        country: 'US',
        timezone: 'America/New_York',
        languages: ['en'],
        currencies: ['USD', 'CAD'],
    )->execute();

    expect($company->currencies)->toEqual(['USD', 'CAD']);
});

it('updates the same location when submitted again', function () {
    Queue::fake();

    $user = owner();

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();

    $location = Location::query()->where('company_id', $user->company_id)->firstOrFail();
    $location->update(['email' => 'scranton@dundermifflin.com', 'address_line_2' => 'Suite 200']);

    new SetUpSalon(
        author: $user,
        name: 'Dunder Mifflin Scranton',
        country: 'US',
        timezone: 'America/Chicago',
        languages: ['en'],
        currencies: ['CAD'],
        city: 'Scranton',
    )->execute();

    expect(Location::query()->where('company_id', $user->company_id)->count())->toEqual(1);

    $location->refresh();

    expect($location->name)->toEqual('Dunder Mifflin Scranton');
    expect($location->timezone)->toEqual('America/Chicago');
    expect($location->city)->toEqual('Scranton');
    expect($location->email)->toEqual('scranton@dundermifflin.com');
    expect($location->address_line_2)->toEqual('Suite 200');
});

it('reuses a location already named like the salon', function () {
    Queue::fake();

    $user = owner();
    Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Dunder Mifflin',
        'is_headquarters' => false,
    ]);

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();

    expect(Location::query()->where('company_id', $user->company_id)->count())->toEqual(1);
    expect(Location::query()->where('company_id', $user->company_id)->firstOrFail()->is_headquarters)->toBeTrue();
});

it('refuses a user who cannot manage the company', function () {
    $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();
})->throws(ModelNotFoundException::class);

it('leaves the catalogue untouched when the languages change', function () {
    Queue::fake();

    $user = owner();
    $service = Service::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Haircut']]);

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en', 'es'], currencies: ['CAD'])->execute();
    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['es'], currencies: ['CAD'])->execute();

    expect($service->refresh()->name)->toEqual(['en' => 'Haircut']);
    expect($service->missingLanguages())->toEqual(['es']);
});

it('leaves the prices untouched when the currencies change', function () {
    Queue::fake();

    $user = owner();
    $service = Service::factory()->create(['company_id' => $user->company_id, 'prices' => ['CAD' => 3500]]);

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD', 'USD'])->execute();

    expect($service->refresh()->prices)->toEqual(['CAD' => 3500]);
    expect($service->missingCurrencies())->toEqual(['USD']);

    $service->update(['prices' => ['CAD' => 3500, 'USD' => 2600]]);

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();
    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD', 'USD'])->execute();

    expect($service->refresh()->prices)->toEqual(['CAD' => 3500, 'USD' => 2600]);
    expect($service->missingCurrencies())->toEqual([]);
});

it('offers the catalogue at the first location', function () {
    Queue::fake();

    $user = owner();
    new CreateDefaultCatalogue(company: $user->company)->execute();

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();

    $scranton = Location::query()->where('company_id', $user->company_id)->firstOrFail();

    expect(LocationService::query()->where('location_id', $scranton->id)->whereNull('prices')->where('is_active', true)->count())->toEqual(17);
});

it('does not offer the catalogue twice when submitted again', function () {
    Queue::fake();

    $user = owner();
    new CreateDefaultCatalogue(company: $user->company)->execute();

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();
    new SetUpSalon(author: $user, name: 'Dunder Mifflin Scranton', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();

    expect(LocationService::query()->count())->toEqual(17);
});

it('gets the catalogue offered when it is created after the first location', function () {
    Queue::fake();

    $user = owner();

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();
    new CreateDefaultCatalogue(company: $user->company)->execute();

    $scranton = Location::query()->where('company_id', $user->company_id)->firstOrFail();

    expect(LocationService::query()->where('location_id', $scranton->id)->count())->toEqual(17);
});

it('does not offer a default service deleted before the first location', function () {
    Queue::fake();

    $user = owner();
    new CreateDefaultCatalogue(company: $user->company)->execute();
    Service::query()->where('company_id', $user->company_id)->where('name->en', 'Balayage')->delete();

    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();

    expect(LocationService::query()->count())->toEqual(16);
});

it('does not offer the catalogue at a location created later', function () {
    Queue::fake();

    $user = owner();
    new CreateDefaultCatalogue(company: $user->company)->execute();
    new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'], currencies: ['CAD'])->execute();

    $stamford = new CreateLocation(author: $user, company: $user->company, name: 'Stamford')->execute();

    expect(LocationService::query()->where('location_id', $stamford->id)->count())->toEqual(0);
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'legal_name' => 'Dunder Mifflin Inc.',
    ]);
    $user = User::factory()->create(['company_id' => $company->id]);

    return test()->giveDefaultRole($user, Role::OWNER);
}
