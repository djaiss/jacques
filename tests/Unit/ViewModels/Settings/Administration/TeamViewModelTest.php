<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration\TeamViewModelTest;

use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use App\ViewModels\Settings\Administration\TeamViewModel;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function rowOf(User $owner, Staff $staff): array
{
    return collect(new TeamViewModel(user: $owner)->rows())->firstWhere('id', $staff->id);
}

it('gives each state of an invitation its actions', function (string $state, string $status, array $kinds) {
    $michael = owner();
    $phyllis = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'phyllis@dundermifflin.com']);
    $factory = StaffInvitation::factory();
    $factory = $state === 'prepared' ? $factory : $factory->{$state}();
    $factory->create(['staff_id' => $phyllis->id]);

    $row = rowOf($michael, $phyllis);

    expect($row['status'])->toBe($status);
    expect(array_column($row['actions'], 'kind'))->toBe($kinds);
})->with('invitation states');

dataset('invitation states', fn (): array => [
    'prepared' => ['prepared', 'Prepared', ['send', 'cancel']],
    'pending' => ['sent', 'Pending', ['resend', 'revoke']],
    'expired' => ['expired', 'Expired', ['send']],
    'revoked' => ['revoked', 'Revoked', []],
]);

it('offers to prepare a new invitation once one is revoked', function () {
    $michael = owner();
    $phyllis = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'phyllis@dundermifflin.com']);
    StaffInvitation::factory()->revoked()->create(['staff_id' => $phyllis->id]);

    expect(rowOf($michael, $phyllis)['canPrepare'])->toBeTrue();
});

it('offers to manage the access of somebody with an account', function () {
    $michael = owner();
    $oscar = User::factory()->create(['company_id' => $michael->company_id]);
    $role = Role::query()->where('company_id', $michael->company_id)->where('slug', Role::STAFF)->firstOrFail();
    $staff = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $oscar->id, 'email' => 'oscar@dundermifflin.com']);
    StaffInvitation::factory()->accepted()->create(['staff_id' => $staff->id, 'role_id' => $role->id]);

    $row = rowOf($michael, $staff);

    expect($row['status'])->toBe('Accepted');
    expect($row['actions'][0]['kind'])->toBe('manage');
    expect($row['actions'][0]['url'])->toBe(route('settings.roles.show', ['role' => $role->id, 'tab' => 'people']));
    expect($row['canPrepare'])->toBeFalse();
});

it('says a staff member without an email address cannot be invited', function () {
    $michael = owner();
    $creed = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => null]);

    $row = rowOf($michael, $creed);

    expect($row['status'])->toBeNull();
    expect($row['canPrepare'])->toBeFalse();
    expect($row['sentence'])->toBe('Add an email address to their profile to invite them.');
});

it('leaves out the team of another company and inactive members', function () {
    $michael = owner();
    Staff::factory()->create();
    Staff::factory()->inactive()->create(['company_id' => $michael->company_id]);

    expect(new TeamViewModel(user: $michael)->rows())->toHaveCount(0);
});

it('says somebody signs in with an account they were never invited to', function () {
    $michael = owner();
    $oscar = User::factory()->create(['company_id' => $michael->company_id]);
    $staff = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $oscar->id]);

    expect(rowOf($michael, $staff)['status'])->toBe('Has an account');
});
