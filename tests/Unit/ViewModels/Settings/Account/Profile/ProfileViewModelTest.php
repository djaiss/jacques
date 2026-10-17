<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Settings\Account\Profile\ProfileViewModel;

it('gives the details of the staff member', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Stanley',
        'last_name' => 'Hudson',
        'display_name' => null,
        'email' => 'stanley.hudson@dundermifflin.com',
        'user_id' => $user->id,
    ]);

    $viewModel = new ProfileViewModel(user: $user, staff: $staff);

    expect($viewModel->details())->toEqual([
        'first_name' => 'Stanley',
        'last_name' => 'Hudson',
        'display_name' => null,
        'work_email' => 'stanley.hudson@dundermifflin.com',
    ]);
});

it('gives the name the staff member goes by', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Jim',
        'last_name' => 'Halpert',
        'display_name' => 'Big Tuna',
        'user_id' => $user->id,
    ]);

    $viewModel = new ProfileViewModel(user: $user, staff: $staff);

    expect($viewModel->name())->toEqual('Big Tuna');
});

it('falls back to the email when there is no staff record', function () {
    $user = User::factory()->create([
        'email' => 'accountant@vancerefrigeration.com',
    ]);

    $viewModel = new ProfileViewModel(user: $user, staff: null);

    expect($viewModel->name())->toEqual('accountant@vancerefrigeration.com');
    expect($viewModel->details())->toEqual([
        'first_name' => null,
        'last_name' => null,
        'display_name' => null,
        'work_email' => null,
    ]);
});

it('gives the company name and the email', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'dwight.schrute@dundermifflin.com',
    ]);

    $viewModel = new ProfileViewModel(user: $user, staff: null);

    expect($viewModel->companyName())->toEqual('Dunder Mifflin');
    expect($viewModel->email())->toEqual('dwight.schrute@dundermifflin.com');
});
