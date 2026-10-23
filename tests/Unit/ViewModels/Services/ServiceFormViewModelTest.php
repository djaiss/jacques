<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Services\ServiceFormViewModelTest;

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Services\ServiceFormViewModel;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin', 'languages' => ['en', 'fr'], 'currencies' => ['CAD', 'USD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('prepares a new service in the category it was asked from', function () {
    $michael = owner();
    $haircuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);

    $viewModel = new ServiceFormViewModel(user: $michael, category: $haircuts);

    expect($viewModel->isNew())->toBeTrue();
    expect($viewModel->saveLabel())->toBe('Add this service');
    expect($viewModel->categoryId())->toBe($haircuts->id);
    expect($viewModel->names())->toBe(['en' => '', 'fr' => '']);
    expect($viewModel->steps())->toHaveCount(1);
    expect($viewModel->method())->toBe('post');
});

it('prepares a service to edit with its prices and salons', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Jim Halpert', 'is_active' => true]);
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Pam Beesly', 'is_active' => true]);
    $jim->locations()->attach($scranton->id);
    $pam->locations()->attach($scranton->id);
    $haircut = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => "Men's Haircut"], 'prices' => ['CAD' => 3550]]);
    LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id])->staff()->attach($jim->id);

    $viewModel = new ServiceFormViewModel(user: $michael, service: $haircut);
    $salons = collect($viewModel->salons())->keyBy('name');

    expect($viewModel->saveLabel())->toBe('Save changes');
    expect($viewModel->prices()[0]['amount'])->toBe('35.50');
    expect($viewModel->prices()[1]['amount'])->toBe('');
    expect($salons['Scranton']['isOn'])->toBeTrue();
    expect(collect($salons['Scranton']['people'])->pluck('isOn', 'name')->all())->toBe(['Jim Halpert' => true, 'Pam Beesly' => false]);
    expect($salons['Stamford']['isOn'])->toBeFalse();
    expect($viewModel->breadcrumb())->toBe([
        'Dunder Mifflin' => route('dashboard.index'),
        'Services' => route('services.index'),
        "Men's Haircut" => route('services.show', $haircut->id),
        'Edit' => null,
    ]);
    expect($stamford->exists)->toBeTrue();
});

it('offers only the people active at a salon', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);
    $ryan = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Ryan Howard', 'is_active' => true]);
    $ryan->locations()->attach($scranton->id, ['is_active' => false]);
    $toby = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Toby Flenderson', 'is_active' => false]);
    $toby->locations()->attach($scranton->id);

    $salons = (new ServiceFormViewModel(user: $michael))->salons();

    expect($salons[0]['people'])->toBe([]);
});
