<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
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
        $this->assertFalse($viewModel->isSetUp());
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
        $this->assertTrue($viewModel->isSetUp());
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

    private function user(OnboardingStepEnum $reached = OnboardingStepEnum::Location): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => $reached,
        ]);

        return User::factory()->create(['company_id' => $company->id]);
    }
}
