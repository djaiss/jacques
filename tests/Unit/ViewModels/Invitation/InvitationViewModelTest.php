<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Invitation\InvitationViewModelTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use App\ViewModels\Invitation\InvitationViewModel;

const TOKEN = 'dwightschrutebeetfarmassistanttotheregionalmanagerscrantonbranch';

function invitation(array $state = []): StaffInvitation
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $dwight = Staff::factory()->create(['company_id' => $company->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'email' => 'dwight@dundermifflin.com']);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Manager']);

    return StaffInvitation::factory()->sent()->create(['staff_id' => $dwight->id, 'role_id' => $role->id, ...$state]);
}

function viewModel(?StaffInvitation $invitation, ?User $viewer = null): InvitationViewModel
{
    return new InvitationViewModel(token: TOKEN, invitation: $invitation, viewer: $viewer);
}

it('asks somebody new for a password', function () {
    $viewModel = viewModel(invitation());

    expect($viewModel->variant())->toBe(InvitationViewModel::CREATE_ACCOUNT);
    expect($viewModel->isError())->toBeFalse();
    expect($viewModel->title())->toBe('Join Dunder Mifflin');
    expect($viewModel->message())->toBe('You’ve been invited to join the team as Manager.');
    expect($viewModel->staffName())->toBe('Dwight Schrute');
    expect($viewModel->email())->toBe('dwight@dundermifflin.com');
    expect($viewModel->acceptUrl())->toBe(route('invitations.create', ['token' => TOKEN]));
});

it('asks somebody with an account of the salon to sign in', function () {
    $invitation = invitation();
    User::factory()->create(['company_id' => $invitation->staff->company_id, 'email' => 'dwight@dundermifflin.com']);

    expect(viewModel($invitation)->variant())->toBe(InvitationViewModel::SIGN_IN);
});

it('asks the invited account signed in to confirm', function () {
    $invitation = invitation();
    $dwight = User::factory()->create(['company_id' => $invitation->staff->company_id, 'email' => 'dwight@dundermifflin.com']);

    expect(viewModel($invitation, $dwight)->variant())->toBe(InvitationViewModel::CONFIRM);
});

it('asks somebody signed in with another account to switch', function () {
    $invitation = invitation();
    $michael = User::factory()->create(['company_id' => $invitation->staff->company_id, 'email' => 'michael@dundermifflin.com']);

    $viewModel = viewModel($invitation, $michael);

    expect($viewModel->variant())->toBe(InvitationViewModel::SWITCH_ACCOUNT);
    expect($viewModel->message())->toContain('dwight@dundermifflin.com');
    expect($viewModel->message())->toContain('michael@dundermifflin.com');
});

it('refuses an address already used by another salon', function () {
    $invitation = invitation();
    User::factory()->create(['email' => 'dwight@dundermifflin.com']);

    $viewModel = viewModel($invitation);

    expect($viewModel->variant())->toBe(InvitationViewModel::OTHER_SALON);
    expect($viewModel->isError())->toBeTrue();
});

it('names why the link no longer works', function (array $state, string $variant, string $title) {
    $viewModel = viewModel(invitation($state));

    expect($viewModel->variant())->toBe($variant);
    expect($viewModel->isError())->toBeTrue();
    expect($viewModel->title())->toBe($title);
})->with('closed invitations');

dataset('closed invitations', fn (): array => [
    'accepted' => [['accepted_at' => now()], InvitationViewModel::ACCEPTED, 'This invitation was already used'],
    'revoked' => [['revoked_at' => now()], InvitationViewModel::REVOKED, 'This invitation was withdrawn'],
    'expired' => [['sent_at' => now()->subDays(8), 'expires_at' => now()->subDay()], InvitationViewModel::EXPIRED, 'This invitation has expired'],
]);

it('says a link it does not know is no longer valid', function () {
    $viewModel = viewModel(null);

    expect($viewModel->variant())->toBe(InvitationViewModel::MISSING);
    expect($viewModel->title())->toBe('This invitation is no longer valid');
});

it('says the link is no longer valid once the staff member is deactivated', function () {
    $invitation = invitation();
    $invitation->staff->update(['is_active' => false]);

    expect(viewModel($invitation->refresh())->variant())->toBe(InvitationViewModel::UNAVAILABLE);
});
