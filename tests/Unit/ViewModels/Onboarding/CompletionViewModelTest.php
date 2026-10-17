<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use App\ViewModels\Onboarding\CompletionViewModel;

it('counts the invitations prepared and not sent', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = User::factory()->create(['company_id' => $company->id]);
    StaffInvitation::factory()->create(['staff_id' => Staff::factory()->create(['company_id' => $company->id])->id]);
    StaffInvitation::factory()->create(['staff_id' => Staff::factory()->create(['company_id' => $company->id])->id]);
    StaffInvitation::factory()->sent()->create(['staff_id' => Staff::factory()->create(['company_id' => $company->id])->id]);
    StaffInvitation::factory()->create();

    $viewModel = new CompletionViewModel(user: $michael);

    expect($viewModel->preparedCount())->toBe(2);
    expect($viewModel->hasPreparedInvitations())->toBeTrue();
    expect($viewModel->invitationSentence())->toBe('2 invitations are ready to send.');
    expect($viewModel->sendLabel())->toBe('Send 2 invitations');
    expect($viewModel->title())->toBe('Dunder Mifflin is ready');
});

it('has nothing to send when no invitation was prepared', function () {
    $michael = User::factory()->create();

    expect(new CompletionViewModel(user: $michael)->hasPreparedInvitations())->toBeFalse();
});
