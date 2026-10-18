<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration\TeamNoAccountViewModelTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use App\ViewModels\Settings\Administration\TeamNoAccountViewModel;

it('lists only the people without an account', function () {
    $company = Company::factory()->create();
    $owner = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $michael = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $owner->id]);
    $pam = Staff::factory()->create(['company_id' => $company->id, 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->sent()->create(['staff_id' => $pam->id]);
    $toby = Staff::factory()->create(['company_id' => $company->id, 'email' => 'toby@dundermifflin.com']);

    $viewModel = new TeamNoAccountViewModel(user: $owner);

    expect(array_column($viewModel->rows(), 'id'))->toBe([$toby->id]);
    expect(collect($viewModel->tabs())->firstWhere('isCurrent', true)['label'])->toBe('No account');
    expect(collect($viewModel->tabs())->pluck('count')->all())->toBe([3, 1, 1, 1]);
});
