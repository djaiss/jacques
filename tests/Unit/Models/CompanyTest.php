<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_many_users(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        User::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertTrue($company->users()->exists());
        $this->assertCount(2, $company->users()->get());
    }

    #[Test]
    public function it_has_many_locations(): void
    {
        $company = Company::factory()->create();
        Location::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertTrue($company->locations()->exists());
        $this->assertCount(2, $company->locations()->get());
    }

    #[Test]
    public function it_has_many_service_categories_in_the_order_of_their_position(): void
    {
        $company = Company::factory()->create();
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
        $cuts = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);

        $this->assertTrue($company->serviceCategories()->exists());
        $this->assertEquals([$cuts->id, $colours->id], $company->serviceCategories->pluck('id')->all());
    }

    #[Test]
    public function it_has_many_services(): void
    {
        $company = Company::factory()->create();
        Service::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertTrue($company->services()->exists());
        $this->assertCount(2, $company->services()->get());
    }

    #[Test]
    public function it_knows_whether_it_is_on_trial(): void
    {
        $onTrial = Company::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $expired = Company::factory()->create(['trial_ends_at' => now()->subDay()]);
        $never = Company::factory()->create(['trial_ends_at' => null]);

        $this->assertTrue($onTrial->isOnTrial());
        $this->assertFalse($expired->isOnTrial());
        $this->assertFalse($never->isOnTrial());
    }

    #[Test]
    public function it_does_not_owe_the_welcome_screen_by_default(): void
    {
        $company = Company::factory()->create();
        $pending = Company::factory()->welcomeScreenPending()->create();

        $this->assertFalse($company->refresh()->is_welcome_screen_pending);
        $this->assertTrue($pending->refresh()->is_welcome_screen_pending);
    }

    #[Test]
    public function it_does_not_owe_the_onboarding_by_default(): void
    {
        $company = Company::factory()->create();
        $pending = Company::factory()->onboardingPending()->create();

        $this->assertFalse($company->refresh()->is_onboarding_pending);
        $this->assertTrue($pending->refresh()->is_onboarding_pending);
        $this->assertEquals(OnboardingStepEnum::Location, $pending->onboarding_step);
    }

    #[Test]
    public function it_keeps_its_languages_in_the_order_they_were_chosen(): void
    {
        $company = Company::factory()->create([
            'languages' => ['fr', 'en'],
        ]);

        $this->assertEquals(['fr', 'en'], $company->refresh()->languages);
    }

    #[Test]
    public function it_has_not_received_the_default_catalogue_by_default(): void
    {
        $company = Company::factory()->create();

        $this->assertNull($company->refresh()->default_catalogue_created_at);
    }

    #[Test]
    public function it_knows_when_it_received_the_default_catalogue(): void
    {
        $company = Company::factory()->create([
            'default_catalogue_created_at' => '2026-10-14 09:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $company->refresh()->default_catalogue_created_at);
    }
}
