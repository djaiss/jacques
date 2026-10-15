<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\ViewModels\Onboarding\LocationViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prefills_the_salon_name_with_the_company_name(): void
    {
        $viewModel = new LocationViewModel(user: $this->user());

        $form = $viewModel->form();

        $this->assertEquals('Dunder Mifflin', $form['name']);
        $this->assertEquals('', $form['country']);
        $this->assertEquals('', $form['timezone']);
    }

    #[Test]
    public function it_prefills_the_form_with_the_first_location(): void
    {
        $user = $this->user(OnboardingStepEnum::Services);
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

        $this->assertEquals('Scranton', $form['city']);
        $this->assertEquals('US', $form['country']);
        $this->assertEquals('America/New_York', $form['timezone']);
    }

    #[Test]
    public function it_lists_every_country_by_its_name(): void
    {
        $countries = new LocationViewModel(user: $this->user())->countries();

        $this->assertGreaterThan(200, count($countries));
        $this->assertEquals('Canada', $countries['CA']);
        $this->assertEquals('United States', $countries['US']);
        $this->assertArrayNotHasKey('??', $countries);
    }

    #[Test]
    public function it_names_the_countries_in_the_language_of_the_user(): void
    {
        app()->setLocale('fr_FR');

        $countries = new LocationViewModel(user: $this->user())->countries();

        $this->assertEquals('États-Unis', $countries['US']);
    }

    #[Test]
    public function it_groups_the_time_zones_by_country(): void
    {
        $timezones = new LocationViewModel(user: $this->user())->timezonesByCountry();

        $this->assertContains('America/Toronto', array_column($timezones['CA'], 'value'));
        $this->assertNotContains('America/New_York', array_column($timezones['CA'], 'value'));
        $this->assertEquals([['value' => 'Europe/Paris', 'label' => 'Central European Time · Paris']], $timezones['FR']);
    }

    #[Test]
    public function it_prefills_the_languages_of_the_company(): void
    {
        $user = $this->user();
        $user->company->update(['languages' => ['fr', 'en']]);

        $viewModel = new LocationViewModel(user: $user);

        $this->assertEquals(['fr', 'en'], $viewModel->form()['languages']);
    }

    #[Test]
    public function it_offers_every_catalogue_language_by_its_own_name(): void
    {
        $viewModel = new LocationViewModel(user: $this->user());

        $languages = collect($viewModel->languages());

        $this->assertEquals(['en', 'fr', 'es', 'pt', 'it', 'de', 'ar', 'zh'], $languages->pluck('code')->all());
        $this->assertEquals('Français', $languages->firstWhere('code', 'fr')['label']);
        $this->assertNotEmpty($languages->firstWhere('code', 'fr')['flag']);
    }

    #[Test]
    public function it_chooses_the_usual_languages_of_each_country(): void
    {
        $viewModel = new LocationViewModel(user: $this->user());

        $languages = $viewModel->languagesByCountry();

        $this->assertEquals(['en', 'fr'], $languages['CA']);
        $this->assertEquals(['fr', 'de'], $languages['CH']);
        $this->assertEquals(['en'], $languages['US']);
        $this->assertEquals(['en'], $languages['JP']);
    }

    #[Test]
    public function it_counts_the_languages_chosen(): void
    {
        $viewModel = new LocationViewModel(user: $this->user());

        $counters = $viewModel->languageCounters();

        $this->assertEquals('1 language', $counters[1]);
        $this->assertEquals('2 languages', $counters[2]);
        $this->assertCount(8, $counters);
    }

    #[Test]
    public function it_lists_the_languages_holding_texts_of_the_catalogue(): void
    {
        $user = $this->user();
        $user->company->update(['languages' => ['en', 'fr', 'es']]);
        ServiceCategory::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Cuts'], 'description' => ['es' => 'Cortes']]);
        Service::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Haircut', 'fr' => '', 'de' => 'Haarschnitt']]);

        $viewModel = new LocationViewModel(user: $user->refresh());

        $this->assertEquals([
            ['code' => 'en', 'label' => 'English'],
            ['code' => 'es', 'label' => 'Español'],
        ], $viewModel->languagesWithTexts());
    }

    #[Test]
    public function it_prefills_the_currencies_of_the_company(): void
    {
        $user = $this->user();
        $user->company->update(['currencies' => ['USD', 'CAD']]);

        $viewModel = new LocationViewModel(user: $user);

        $this->assertEquals(['USD', 'CAD'], $viewModel->form()['currencies']);
    }

    #[Test]
    public function it_offers_every_currency_by_its_name(): void
    {
        $viewModel = new LocationViewModel(user: $this->user());

        $currencies = collect($viewModel->currencies());

        $this->assertEquals(['CAD', 'USD', 'EUR', 'GBP', 'CHF'], $currencies->pluck('code')->all());
        $this->assertEquals('Canadian dollar', $currencies->firstWhere('code', 'CAD')['label']);
    }

    #[Test]
    public function it_chooses_the_currency_of_each_country(): void
    {
        $viewModel = new LocationViewModel(user: $this->user());

        $currencies = $viewModel->currenciesByCountry();

        $this->assertEquals('CAD', $currencies['CA']);
        $this->assertEquals('USD', $currencies['US']);
        $this->assertEquals('EUR', $currencies['FR']);
        $this->assertEquals('CHF', $currencies['CH']);
        $this->assertEquals('CAD', $currencies['JP']);
    }

    #[Test]
    public function it_counts_the_currencies_chosen(): void
    {
        $viewModel = new LocationViewModel(user: $this->user());

        $counters = $viewModel->currencyCounters();

        $this->assertEquals('1 currency', $counters[1]);
        $this->assertEquals('2 currencies', $counters[2]);
        $this->assertCount(5, $counters);
    }

    #[Test]
    public function it_lists_the_currencies_holding_prices_of_the_catalogue(): void
    {
        $user = $this->user();
        $user->company->update(['currencies' => ['CAD', 'USD', 'EUR', 'CHF']]);
        $scranton = Location::factory()->create(['company_id' => $user->company_id]);
        $haircut = Service::factory()->create(['company_id' => $user->company_id, 'prices' => ['CAD' => 3500, 'GBP' => 2000]]);
        LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id, 'prices' => ['EUR' => 2500]]);
        LocationService::factory()->create(['prices' => ['CHF' => 3000]]);

        $viewModel = new LocationViewModel(user: $user->refresh());

        $this->assertEquals([
            ['code' => 'CAD', 'label' => 'Canadian dollar'],
            ['code' => 'EUR', 'label' => 'Euro'],
        ], $viewModel->currenciesWithPrices());
    }

    private function user(OnboardingStepEnum $reached = OnboardingStepEnum::Location): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => $reached,
        ]);

        return User::factory()->create(['company_id' => $company->id]);
    }
}
