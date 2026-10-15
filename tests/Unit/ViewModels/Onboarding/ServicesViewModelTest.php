<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\User;
use App\ViewModels\Onboarding\ServicePanel;
use App\ViewModels\Onboarding\ServicesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServicesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_categories_with_their_services_rows(): void
    {
        $michael = $this->owner();
        $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts', 'fr' => 'Coupes'], 'position' => 1]);
        ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => [], 'position' => 2]);
        $haircut = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $cuts->id, 'name' => ['en' => 'Haircut'], 'description' => null, 'prices' => ['CAD' => 3550]]);

        $categories = new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services)->categories();

        $this->assertCount(2, $categories);
        $this->assertTrue($categories[0]['isFirst']);
        $this->assertTrue($categories[1]['isLast']);
        $this->assertEquals('Untitled category', $categories[1]['texts']['en']['shown']);
        $this->assertEquals(['en' => 0, 'fr' => 1], $categories[0]['missing']);

        $row = $categories[0]['services'][0];

        $this->assertEquals($haircut->id, $row['id']);
        $this->assertEquals(['value' => '', 'shown' => 'Haircut', 'isMissing' => true], $row['texts']['fr']);
        $this->assertEquals('35.50', $row['prices'][0]['amount']);
        $this->assertEquals('', $row['prices'][1]['amount']);
        $this->assertEquals('No US$ price', $row['missingPriceLabel']);
        $this->assertFalse($row['isMultiStep']);
    }

    #[Test]
    public function it_summarizes_the_catalogue(): void
    {
        $michael = $this->owner();
        $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
        $colour = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $cuts->id, 'prices' => []]);
        ServiceStep::factory()->create(['service_id' => $colour->id, 'position' => 2, 'requires_staff' => false]);
        Service::factory()->create(['company_id' => $michael->company_id, 'prices' => ['CAD' => 0, 'USD' => 0]]);

        $viewModel = new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services);

        $this->assertTrue($viewModel->hasCatalogue());
        $this->assertEquals(2, $viewModel->serviceCount());
        $this->assertEquals('2 services in 1 category. Keep what you offer, remove the rest.', $viewModel->summary());
        $this->assertEquals('1 service missing a price in at least one currency', $viewModel->missingPriceSummary());
        $this->assertEquals('2 services in your catalog', $viewModel->footerStatus());
        $this->assertCount(1, $viewModel->uncategorizedServices());
        $this->assertTrue($viewModel->categories()[0]['services'][0]['isMultiStep']);
    }

    #[Test]
    public function it_speaks_about_an_empty_catalogue(): void
    {
        $viewModel = new ServicesViewModel(user: $this->owner(), step: OnboardingStepEnum::Services);

        $this->assertFalse($viewModel->hasCatalogue());
        $this->assertEquals('Start from scratch, or from our examples.', $viewModel->summary());
        $this->assertEquals('No services yet. You can add them later from Settings.', $viewModel->footerStatus());
        $this->assertNull($viewModel->panel());
    }

    #[Test]
    public function it_opens_the_panel_of_one_service_and_reads_the_last_removal(): void
    {
        $michael = $this->owner();
        $haircut = Service::factory()->create(['company_id' => $michael->company_id]);
        session()->put('onboarding.removal', ['message' => '“Balayage” removed.', 'snapshot' => []]);

        $viewModel = new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services, openService: $haircut);

        $this->assertInstanceOf(ServicePanel::class, $viewModel->panel());
        $this->assertEquals('“Balayage” removed.', $viewModel->removalMessage());
    }

    #[Test]
    public function it_knows_whether_the_salon_has_several_languages(): void
    {
        $michael = $this->owner();

        $this->assertTrue(new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services)->hasSeveralLanguages());

        $michael->company->update(['languages' => ['en']]);

        $this->assertFalse(new ServicesViewModel(user: $michael->refresh(), step: OnboardingStepEnum::Services)->hasSeveralLanguages());
    }

    private function owner(): User
    {
        $company = Company::factory()->create(['languages' => ['en', 'fr'], 'currencies' => ['CAD', 'USD']]);

        return User::factory()->create(['company_id' => $company->id]);
    }
}
