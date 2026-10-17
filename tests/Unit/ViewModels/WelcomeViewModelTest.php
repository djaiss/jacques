<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\WelcomeViewModel;

it('gives the first name of the person', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Pam',
        'last_name' => 'Beesly',
        'user_id' => $user->id,
    ]);

    $viewModel = new WelcomeViewModel(user: $user);

    expect($viewModel->firstName())->toEqual('Pam');
});

it('falls back to the email address without a staff record', function () {
    $user = User::factory()->create([
        'email' => 'pam.beesly@dundermifflin.com',
    ]);

    $viewModel = new WelcomeViewModel(user: $user);

    expect($viewModel->firstName())->toEqual('pam.beesly@dundermifflin.com');
});
