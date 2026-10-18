<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration\TeamViewModelTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use App\ViewModels\Settings\Administration\TeamViewModel;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $michael = test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $michael->id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'display_name' => null]);

    return $michael;
}

function roleOf(User $owner, string $slug): Role
{
    return Role::query()->where('company_id', $owner->company_id)->where('slug', $slug)->firstOrFail();
}

function rowOf(User $owner, Staff $staff): array
{
    return collect(new TeamViewModel(user: $owner)->rows())->firstWhere('id', $staff->id);
}

function panelOf(User $owner, Staff $staff): array
{
    return new TeamViewModel(user: $owner, selected: $staff)->panel();
}

it('lists every active member with their role and state', function () {
    $michael = owner();
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $pam->id, 'role_id' => roleOf($michael, Role::STAFF)->id]);
    $toby = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'toby@dundermifflin.com']);
    Staff::factory()->create(['company_id' => $michael->company_id, 'is_active' => false]);
    Staff::factory()->create();

    $rows = collect(new TeamViewModel(user: $michael)->rows());

    expect($rows)->toHaveCount(3);
    expect($rows[0]['isViewer'])->toBeTrue();
    expect($rows[0]['role'])->toBe(roleOf($michael, Role::OWNER)->name);
    expect($rows[0]['state'])->toBe('Signs in');
    expect(rowOf($michael, $pam)['state'])->toBe('Invitation not sent');
    expect(rowOf($michael, $pam)['role'])->toBe(roleOf($michael, Role::STAFF)->name);
    expect(rowOf($michael, $toby)['state'])->toBe('No account');
    expect(rowOf($michael, $toby)['isViewer'])->toBeFalse();
    expect(rowOf($michael, $toby)['manageUrl'])->toBe(route('settings.team.show', $toby->id));
});

it('counts the people of each tab and marks everybody as the current one', function () {
    $michael = owner();
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'pam@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $pam->id]);
    Staff::factory()->create(['company_id' => $michael->company_id]);

    $tabs = collect(new TeamViewModel(user: $michael)->tabs());

    expect($tabs->pluck('count')->all())->toBe([3, 1, 1, 1]);
    expect($tabs->pluck('url')->all())->toBe([
        route('settings.team.index'),
        route('settings.teamSignsIn.index'),
        route('settings.teamInvited.index'),
        route('settings.teamNoAccount.index'),
    ]);
    expect($tabs->pluck('isCurrent')->all())->toBe([true, false, false, false]);
});

it('gives each state of an account its actions', function (?string $state, string $label, array $kinds) {
    $michael = owner();
    $phyllis = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'phyllis@dundermifflin.com']);

    if ($state !== null) {
        $factory = StaffInvitation::factory();
        $factory = $state === 'prepared' ? $factory : $factory->{$state}();
        $factory->create(['staff_id' => $phyllis->id]);
    }

    $panel = panelOf($michael, $phyllis);

    expect($panel['state'])->toBe($label);
    expect(array_column($panel['actions'], 'kind'))->toBe($kinds);
})->with('account states');

dataset('account states', fn (): array => [
    'no account' => [null, 'No account', ['send', 'prepare']],
    'prepared' => ['prepared', 'Invitation not sent', ['send', 'cancel']],
    'pending' => ['sent', 'Invitation sent', ['send', 'cancel']],
    'expired' => ['expired', 'Invitation expired', ['send']],
    'revoked' => ['revoked', 'No account', ['send', 'prepare']],
]);

it('sends the chosen role with an invitation made from nothing', function () {
    $michael = owner();
    $toby = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'toby@dundermifflin.com']);

    $panel = panelOf($michael, $toby);

    expect(array_column($panel['actions'], 'withRole'))->toBe([true, true]);
    expect($panel['roleId'])->toBe(roleOf($michael, Role::STAFF)->id);
    expect($panel['canSave'])->toBeFalse();
});

it('offers nothing to invite somebody without an email address', function () {
    $michael = owner();
    $creed = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => null]);

    $panel = panelOf($michael, $creed);

    expect($panel['actions'])->toBe([]);
    expect($panel['sentence'])->toBe('Add an email address to their profile to invite them.');
});

it('offers an owner to remove the account of somebody else', function () {
    $michael = owner();
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => User::factory()->create(['company_id' => $michael->company_id])->id]);

    $panel = panelOf($michael, $jim);

    expect($panel['state'])->toBe('Signs in');
    expect(array_column($panel['actions'], 'kind'))->toBe(['remove']);
    expect($panel['canSave'])->toBeTrue();
});

it('selects no role for an account that holds none', function () {
    $michael = owner();
    $jim = Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => User::factory()->create(['company_id' => $michael->company_id])->id]);

    expect(panelOf($michael, $jim)['roleId'])->toBeNull();
});

it('never offers to remove the account of the viewer', function () {
    $michael = owner();

    $panel = panelOf($michael, Staff::query()->where('user_id', $michael->id)->firstOrFail());

    expect($panel['actions'])->toBe([]);
});

it('keeps the last active owner on the owner role', function () {
    $michael = owner();

    $panel = panelOf($michael, Staff::query()->where('user_id', $michael->id)->firstOrFail());

    expect($panel['isSoleOwner'])->toBeTrue();
    expect(collect($panel['roles'])->where('disabled', false)->pluck('id')->all())->toBe([roleOf($michael, Role::OWNER)->id]);
});

it('lets every role be picked once another owner exists', function () {
    $michael = owner();
    test()->giveDefaultRole(User::factory()->create(['company_id' => $michael->company_id]), Role::OWNER);

    $panel = panelOf($michael, Staff::query()->where('user_id', $michael->id)->firstOrFail());

    expect($panel['isSoleOwner'])->toBeFalse();
    expect(collect($panel['roles'])->where('disabled', true))->toHaveCount(0);
});

it('shows what each role allows', function () {
    $michael = owner();
    $toby = Staff::factory()->create(['company_id' => $michael->company_id]);

    $permissions = panelOf($michael, $toby)['permissions'];
    $owner = collect($permissions[roleOf($michael, Role::OWNER)->id])->pluck('permissions')->flatten(1);
    $staff = collect($permissions[roleOf($michael, Role::STAFF)->id])->pluck('permissions')->flatten(1);

    expect($owner)->toHaveCount(count(PermissionEnum::cases()));
    expect($owner->every(fn (array $permission): bool => $permission['allowed']))->toBeTrue();
    expect($staff->contains(fn (array $permission): bool => ! $permission['allowed']))->toBeTrue();
});

it('opens no panel when nobody is selected', function () {
    expect(new TeamViewModel(user: owner())->panel())->toBeNull();
});
