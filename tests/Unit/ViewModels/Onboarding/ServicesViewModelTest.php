<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding\ServicesViewModelTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\User;
use App\ViewModels\Onboarding\ServicePanel;
use App\ViewModels\Onboarding\ServicesViewModel;

it('lists the categories with their services rows', function () {
    $michael = owner();
    $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts', 'fr' => 'Coupes'], 'position' => 1]);
    ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => [], 'position' => 2]);
    $haircut = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $cuts->id, 'name' => ['en' => 'Haircut'], 'description' => null, 'prices' => ['CAD' => 3550]]);

    $categories = new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services)->categories();

    expect($categories)->toHaveCount(2);
    expect($categories[0]['isFirst'])->toBeTrue();
    expect($categories[1]['isLast'])->toBeTrue();
    expect($categories[1]['texts']['en']['shown'])->toEqual('Untitled category');
    expect($categories[0]['missing'])->toEqual(['en' => 0, 'fr' => 1]);

    $row = $categories[0]['services'][0];

    expect($row['id'])->toEqual($haircut->id);
    expect($row['texts']['fr'])->toEqual(['value' => '', 'shown' => 'Haircut', 'isMissing' => true]);
    expect($row['prices'][0]['amount'])->toEqual('35.50');
    expect($row['prices'][1]['amount'])->toEqual('');
    expect($row['missingPriceLabel'])->toEqual('No US$ price');
    expect($row['isMultiStep'])->toBeFalse();
});

it('summarizes the catalogue', function () {
    $michael = owner();
    $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
    $colour = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $cuts->id, 'prices' => []]);
    ServiceStep::factory()->create(['service_id' => $colour->id, 'position' => 2, 'requires_staff' => false]);
    Service::factory()->create(['company_id' => $michael->company_id, 'prices' => ['CAD' => 0, 'USD' => 0]]);

    $viewModel = new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services);

    expect($viewModel->hasCatalogue())->toBeTrue();
    expect($viewModel->serviceCount())->toEqual(2);
    expect($viewModel->summary())->toEqual('2 services in 1 category. Keep what you offer, remove the rest.');
    expect($viewModel->missingPriceSummary())->toEqual('1 service missing a price in at least one currency');
    expect($viewModel->footerStatus())->toEqual('2 services in your catalog');
    expect($viewModel->uncategorizedServices())->toHaveCount(1);
    expect($viewModel->categories()[0]['services'][0]['isMultiStep'])->toBeTrue();
});

it('speaks about an empty catalogue', function () {
    $viewModel = new ServicesViewModel(user: owner(), step: OnboardingStepEnum::Services);

    expect($viewModel->hasCatalogue())->toBeFalse();
    expect($viewModel->summary())->toEqual('Start from scratch, or from our examples.');
    expect($viewModel->footerStatus())->toEqual('No services yet. You can add them later from Settings.');
    expect($viewModel->panel())->toBeNull();
});

it('opens the panel of one service and reads the last removal', function () {
    $michael = owner();
    $haircut = Service::factory()->create(['company_id' => $michael->company_id]);
    session()->put('onboarding.removal', ['message' => '“Balayage” removed.', 'snapshot' => []]);

    $viewModel = new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services, openService: $haircut);

    expect($viewModel->panel())->toBeInstanceOf(ServicePanel::class);
    expect($viewModel->removalMessage())->toEqual('“Balayage” removed.');
});

it('knows whether the salon has several languages', function () {
    $michael = owner();

    expect(new ServicesViewModel(user: $michael, step: OnboardingStepEnum::Services)->hasSeveralLanguages())->toBeTrue();

    $michael->company->update(['languages' => ['en']]);

    expect(new ServicesViewModel(user: $michael->refresh(), step: OnboardingStepEnum::Services)->hasSeveralLanguages())->toBeFalse();
});

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en', 'fr'], 'currencies' => ['CAD', 'USD']]);

    return User::factory()->create(['company_id' => $company->id]);
}
