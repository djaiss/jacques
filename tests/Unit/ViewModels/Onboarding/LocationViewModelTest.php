<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding\LocationViewModelTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\ViewModels\Onboarding\LocationViewModel;

it('prefills the salon name with the company name', function () {
    $viewModel = new LocationViewModel(user: user());

    $form = $viewModel->form();

    expect($form['name'])->toEqual('Dunder Mifflin');
    expect($form['country'])->toEqual('');
    expect($form['timezone'])->toEqual('');
});

it('prefills the form with the first location', function () {
    $user = user(OnboardingStepEnum::Services);
    Location::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Dunder Mifflin',
        'city' => 'Scranton',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'is_headquarters' => true,
    ]);

    $viewModel = new LocationViewModel(user: $user);

    $form = $viewModel->form();

    expect($form['city'])->toEqual('Scranton');
    expect($form['country'])->toEqual('US');
    expect($form['timezone'])->toEqual('America/New_York');
});

it('lists every country by its name', function () {
    $countries = new LocationViewModel(user: user())->countries();

    expect(count($countries))->toBeGreaterThan(200);
    expect($countries['CA'])->toEqual('Canada');
    expect($countries['US'])->toEqual('United States');
    expect($countries)->not->toHaveKey('??');
});

it('names the countries in the language of the user', function () {
    app()->setLocale('fr_FR');

    $countries = new LocationViewModel(user: user())->countries();

    expect($countries['US'])->toEqual('États-Unis');
});

it('groups the time zones by country', function () {
    $timezones = new LocationViewModel(user: user())->timezonesByCountry();

    expect(array_column($timezones['CA'], 'value'))->toContain('America/Toronto');
    expect(array_column($timezones['CA'], 'value'))->not->toContain('America/New_York');
    expect($timezones['FR'])->toEqual([['value' => 'Europe/Paris', 'label' => 'Central European Time · Paris']]);
});

it('prefills the languages of the company', function () {
    $user = user();
    $user->company->update(['languages' => ['fr', 'en']]);

    $viewModel = new LocationViewModel(user: $user);

    expect($viewModel->form()['languages'])->toEqual(['fr', 'en']);
});

it('offers every catalogue language by its own name', function () {
    $viewModel = new LocationViewModel(user: user());

    $languages = collect($viewModel->languages());

    expect($languages->pluck('code')->all())->toEqual(['en', 'fr', 'es', 'pt', 'it', 'de', 'ar', 'zh']);
    expect($languages->firstWhere('code', 'fr')['label'])->toEqual('Français');
    expect($languages->firstWhere('code', 'fr')['flag'])->not->toBeEmpty();
});

it('chooses the usual languages of each country', function () {
    $viewModel = new LocationViewModel(user: user());

    $languages = $viewModel->languagesByCountry();

    expect($languages['CA'])->toEqual(['en', 'fr']);
    expect($languages['CH'])->toEqual(['fr', 'de']);
    expect($languages['US'])->toEqual(['en']);
    expect($languages['JP'])->toEqual(['en']);
});

it('counts the languages chosen', function () {
    $viewModel = new LocationViewModel(user: user());

    $counters = $viewModel->languageCounters();

    expect($counters[1])->toEqual('1 language');
    expect($counters[2])->toEqual('2 languages');
    expect($counters)->toHaveCount(8);
});

it('lists the languages holding texts of the catalogue', function () {
    $user = user();
    $user->company->update(['languages' => ['en', 'fr', 'es']]);
    ServiceCategory::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Cuts'], 'description' => ['es' => 'Cortes']]);
    Service::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Haircut', 'fr' => '', 'de' => 'Haarschnitt']]);

    $viewModel = new LocationViewModel(user: $user->refresh());

    expect($viewModel->languagesWithTexts())->toEqual([
        ['code' => 'en', 'label' => 'English'],
        ['code' => 'es', 'label' => 'Español'],
    ]);
});

it('prefills the currencies of the company', function () {
    $user = user();
    $user->company->update(['currencies' => ['USD', 'CAD']]);

    $viewModel = new LocationViewModel(user: $user);

    expect($viewModel->form()['currencies'])->toEqual(['USD', 'CAD']);
});

it('offers every currency by its name', function () {
    $viewModel = new LocationViewModel(user: user());

    $currencies = collect($viewModel->currencies());

    expect($currencies->pluck('code')->all())->toEqual(['CAD', 'USD', 'EUR', 'GBP', 'CHF']);
    expect($currencies->firstWhere('code', 'CAD')['label'])->toEqual('Canadian dollar');
});

it('chooses the currency of each country', function () {
    $viewModel = new LocationViewModel(user: user());

    $currencies = $viewModel->currenciesByCountry();

    expect($currencies['CA'])->toEqual('CAD');
    expect($currencies['US'])->toEqual('USD');
    expect($currencies['FR'])->toEqual('EUR');
    expect($currencies['CH'])->toEqual('CHF');
    expect($currencies['JP'])->toEqual('CAD');
});

it('counts the currencies chosen', function () {
    $viewModel = new LocationViewModel(user: user());

    $counters = $viewModel->currencyCounters();

    expect($counters[1])->toEqual('1 currency');
    expect($counters[2])->toEqual('2 currencies');
    expect($counters)->toHaveCount(5);
});

it('lists the currencies holding prices of the catalogue', function () {
    $user = user();
    $user->company->update(['currencies' => ['CAD', 'USD', 'EUR', 'CHF']]);
    $scranton = Location::factory()->create(['company_id' => $user->company_id]);
    $haircut = Service::factory()->create(['company_id' => $user->company_id, 'prices' => ['CAD' => 3500, 'GBP' => 2000]]);
    LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id, 'prices' => ['EUR' => 2500]]);
    LocationService::factory()->create(['prices' => ['CHF' => 3000]]);

    $viewModel = new LocationViewModel(user: $user->refresh());

    expect($viewModel->currenciesWithPrices())->toEqual([
        ['code' => 'CAD', 'label' => 'Canadian dollar'],
        ['code' => 'EUR', 'label' => 'Euro'],
    ]);
});

function user(OnboardingStepEnum $reached = OnboardingStepEnum::Location): User
{
    $company = Company::factory()->onboardingPending()->create([
        'name' => 'Dunder Mifflin',
        'onboarding_step' => $reached,
    ]);

    return User::factory()->create(['company_id' => $company->id]);
}
