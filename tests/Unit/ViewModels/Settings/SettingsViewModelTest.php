<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Settings\SettingsViewModel;

it('lists every setting of the account', function () {
    $user = User::factory()->create();

    $viewModel = new SettingsViewModel(user: $user, staff: null);

    $titles = array_column($viewModel->accountRows(), 'title');

    expect($titles)->toBe(['Profile', 'Security and access', 'Preferences', 'Logs']);
});

it('says whether two factor authentication is on', function () {
    $user = User::factory()->create(['two_factor_confirmed_at' => now()]);

    $viewModel = new SettingsViewModel(user: $user, staff: null);

    expect($viewModel->accountRows()[1]['value'])->toBe('Two-factor on');
});

it('says when two factor authentication is off', function () {
    $user = User::factory()->create(['two_factor_confirmed_at' => null]);

    $viewModel = new SettingsViewModel(user: $user, staff: null);

    expect($viewModel->accountRows()[1]['value'])->toBe('Two-factor off');
});

it('counts the locations of the company', function () {
    $company = Company::factory()->create();
    $user = $this->grant(
        User::factory()->create(['company_id' => $company->id]),
        PermissionEnum::LocationManage,
    );
    Location::factory()->count(2)->create(['company_id' => $company->id]);

    $viewModel = new SettingsViewModel(user: $user, staff: null);

    expect($viewModel->companyRows()[0]['value'])->toBe('2 locations');
});

it('offers nothing about the company to somebody who administers none of it', function () {
    $user = User::factory()->create();

    $viewModel = new SettingsViewModel(user: $user, staff: null);

    expect($viewModel->companyRows())->toBe([]);
});

it('offers the locations but not the roles to a manager', function () {
    $user = $this->giveDefaultRole(User::factory()->create(), Role::MANAGER);

    $viewModel = new SettingsViewModel(user: $user, staff: null);

    expect(array_column($viewModel->companyRows(), 'title'))->toEqual(['Locations']);
});

it('falls back to the email address when nobody works here', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.example']);

    $viewModel = new SettingsViewModel(user: $user, staff: null);

    expect($viewModel->name())->toBe('michael.scott@dundermifflin.example');
});

it('names the role of somebody', function () {
    $company = Company::factory()->create();
    $user = $this->grant(
        User::factory()->create(['company_id' => $company->id]),
        PermissionEnum::LocationManage,
    );
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    $viewModel = new SettingsViewModel(user: $user, staff: $staff);

    expect($viewModel->role())->toBe('location.manage at company scope');
});
