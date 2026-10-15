<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\ViewModels\Onboarding\ServicePanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServicePanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prepares_a_service_for_the_panel(): void
    {
        $company = Company::factory()->create(['languages' => ['en', 'fr'], 'currencies' => ['CAD', 'USD']]);
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Hair Coloring']]);
        $colour = Service::factory()->create([
            'company_id' => $company->id,
            'service_category_id' => $colours->id,
            'name' => ['en' => 'Full Color'],
            'description' => null,
            'prices' => ['CAD' => 10000],
        ]);
        ServiceStep::factory()->create(['service_id' => $colour->id, 'position' => 2, 'name' => ['en' => 'Processing'], 'duration' => 15, 'requires_staff' => false]);

        $panel = new ServicePanel(
            service: $colour->load('steps'),
            categories: ServiceCategory::query()->where('company_id', $company->id)->get(),
            languages: ['en', 'fr'],
            currencies: [['code' => 'CAD', 'symbol' => 'CA$', 'name' => 'Canadian dollar'], ['code' => 'USD', 'symbol' => 'US$', 'name' => 'US dollar']],
        );

        $this->assertEquals('Full Color', $panel->title());
        $this->assertEquals('Edit service · Hair Coloring', $panel->eyebrow());
        $this->assertEquals(['en' => 'Full Color', 'fr' => ''], $panel->names());
        $this->assertEquals('1 of 2 missing', $panel->priceStatus());
        $this->assertFalse($panel->hasEveryPrice());
        $this->assertEquals('100', $panel->prices()[0]['amount']);
        $this->assertTrue($panel->prices()[1]['isMissing']);
        $this->assertEquals([['id' => $colours->id, 'label' => 'Hair Coloring']], $panel->categoryOptions());
        $this->assertCount(2, $panel->steps());
        $this->assertFalse($panel->steps()[1]['requires_staff']);
        $this->assertEquals('Processing', $panel->processingName()['en']);
        $this->assertEquals('1 to translate in English', $panel->translationStatus()['en']);
        $this->assertEquals('3 to translate in Français', $panel->translationStatus()['fr']);
    }
}
