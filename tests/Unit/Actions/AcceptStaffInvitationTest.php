<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\AcceptStaffInvitationTest;

use App\Actions\AcceptStaffInvitation;
use App\Enums\StaffInvitationStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

const TOKEN = 'pambeeslyreceptionistdundermifflinscrantonbranchartschoolnewyork';

function invitedPam(array $state = []): array
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $pam = Staff::factory()->create(['company_id' => $company->id, 'first_name' => 'Pam', 'last_name' => 'Beesly', 'email' => 'pam@dundermifflin.com']);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Staff']);

    $invitation = StaffInvitation::factory()->sent()->create([
        'staff_id' => $pam->id,
        'role_id' => $role->id,
        'token_hash' => hash('sha256', TOKEN),
        ...$state,
    ]);

    return [$pam, $invitation];
}

it('creates an account for somebody new and links it to their record', function () {
    Queue::fake();

    [$pam, $invitation] = invitedPam();

    $user = new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe('pam@dundermifflin.com');
    expect($user->company_id)->toBe($pam->company_id);
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('jim-and-pam-2005', $user->password_hash))->toBeTrue();
    expect($pam->refresh()->user_id)->toBe($user->id);
    expect($user->roles()->whereKey($invitation->role_id)->exists())->toBeTrue();

    expect($invitation->refresh()->status())->toBe(StaffInvitationStatusEnum::Accepted);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInvitationAccepted,
    );
});

it('links the account signed in when it uses the invited address', function () {
    Queue::fake();

    [$pam] = invitedPam();
    $account = User::factory()->create(['company_id' => $pam->company_id, 'email' => 'pam@dundermifflin.com']);

    $user = new AcceptStaffInvitation(token: TOKEN, user: $account)->execute();

    expect($user->id)->toBe($account->id);
    expect($pam->refresh()->user_id)->toBe($account->id);
});

it('throws when the same link is accepted twice', function () {
    Queue::fake();

    invitedPam();

    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the link expired', function () {
    invitedPam(['sent_at' => now()->subDays(8), 'expires_at' => now()->subDay()]);

    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the invitation was revoked', function () {
    invitedPam(['revoked_at' => now()]);

    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the link was replaced by a newer one', function () {
    invitedPam(['token_hash' => hash('sha256', 'a-newer-token')]);

    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member was deactivated', function () {
    [$pam] = invitedPam();
    $pam->update(['is_active' => false]);

    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the account signed in uses another address', function () {
    [$pam] = invitedPam();
    $michael = User::factory()->create(['company_id' => $pam->company_id, 'email' => 'michael@dundermifflin.com']);

    new AcceptStaffInvitation(token: TOKEN, user: $michael)->execute();
})->throws(InvalidArgumentException::class);

it('throws when an account of another salon uses the invited address', function () {
    invitedPam();
    User::factory()->create(['email' => 'pam@dundermifflin.com']);

    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the role belongs to another company', function () {
    invitedPam(['role_id' => Role::factory()->create()->id]);

    new AcceptStaffInvitation(token: TOKEN, password: 'jim-and-pam-2005')->execute();
})->throws(ModelNotFoundException::class);
