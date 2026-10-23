<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Services\ServiceCategoryFormViewModelTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\ViewModels\Services\ServiceCategoryFormViewModel;

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en', 'fr']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('prepares a new category in every language', function () {
    $viewModel = new ServiceCategoryFormViewModel(user: owner());

    expect($viewModel->isNew())->toBeTrue();
    expect($viewModel->names())->toBe(['en' => '', 'fr' => '']);
    expect($viewModel->action())->toBe(route('serviceCategories.create'));
    expect($viewModel->destroyUrl())->toBeNull();
});

it('warns about the services a removed category leaves behind', function () {
    $michael = owner();
    $makeup = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Makeup']]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $makeup->id, 'name' => ['en' => 'Bridal Makeup']]);

    $viewModel = new ServiceCategoryFormViewModel(user: $michael, category: $makeup);

    expect($viewModel->title())->toBe('Makeup');
    expect($viewModel->removalWarning())->toBe('1 service stays in the catalog, without a category: Bridal Makeup.');
});

it('says an empty category leaves nothing behind', function () {
    $michael = owner();
    $makeup = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);

    expect((new ServiceCategoryFormViewModel(user: $michael, category: $makeup))->removalWarning())->toBe('The category is empty, so nothing else changes.');
});
