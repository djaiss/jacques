<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\SendStaffInvitationTest;

use App\Actions\SendStaffInvitation;
use App\Enums\EmailTypeEnum;
use App\Enums\StaffInvitationStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function pam(User $owner, string $state = 'prepared'): Staff
{
    $pam = Staff::factory()->create(['company_id' => $owner->company_id, 'first_name' => 'Pam', 'last_name' => 'Beesly', 'display_name' => null, 'email' => 'pam@dundermifflin.com']);
    $role = Role::query()->where('company_id', $owner->company_id)->where('slug', Role::STAFF)->firstOrFail();
    $factory = StaffInvitation::factory();

    $factory = match ($state) {
        'sent' => $factory->sent()->state(['sent_at' => now()->subHour()]),
        'expired' => $factory->expired(),
        'accepted' => $factory->accepted(),
        'revoked' => $factory->revoked(),
        default => $factory,
    };

    $factory->create(['staff_id' => $pam->id, 'role_id' => $role->id, 'author_id' => $owner->id]);

    return $pam;
}

it('sends a prepared invitation', function () {
    Queue::fake();

    $michael = owner();
    $pam = pam($michael);

    $invitation = new SendStaffInvitation(author: $michael, staff: $pam)->execute();

    expect($invitation)->toBeInstanceOf(StaffInvitation::class);
    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Pending);
    expect($invitation->token_hash)->toHaveLength(64);
    expect($invitation->expires_at->toDateString())->toEqual(now()->addDays(7)->toDateString());

    Queue::assertPushedOn(
        queue: 'high',
        job: SendEmail::class,
        callback: fn (SendEmail $job): bool => $job->emailType === EmailTypeEnum::StaffInvitation
            && $job->emailAddress === 'pam@dundermifflin.com',
    );

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInvitationSent
            && $job->parameters === ['name' => 'Pam Beesly'],
    );
});

it('draws a new link when a pending invitation is sent again', function () {
    Queue::fake();

    $michael = owner();
    $pam = pam($michael, 'sent');
    $previous = $pam->invitation->token_hash;

    $invitation = new SendStaffInvitation(author: $michael, staff: $pam)->execute();

    expect($invitation->token_hash)->not->toBe($previous);
    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Pending);
});

it('sends an expired invitation again', function () {
    Queue::fake();

    $michael = owner();
    $pam = pam($michael, 'expired');

    $invitation = new SendStaffInvitation(author: $michael, staff: $pam)->execute();

    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Pending);
});

it('sends one email when asked twice in a row', function () {
    Queue::fake();

    $michael = owner();
    $pam = pam($michael);

    new SendStaffInvitation(author: $michael, staff: $pam)->execute();
    new SendStaffInvitation(author: $michael, staff: $pam)->execute();

    Queue::assertPushed(SendEmail::class, 1);
});

it('throws after three sends in a day', function () {
    Queue::fake();

    $michael = owner();
    $pam = pam($michael);
    RateLimiter::hit("staff-invitation:{$pam->id}", 86400);
    RateLimiter::hit("staff-invitation:{$pam->id}", 86400);
    RateLimiter::hit("staff-invitation:{$pam->id}", 86400);

    new SendStaffInvitation(author: $michael, staff: $pam)->execute();
})->throws(ValidationException::class);

it('throws when the invitation was accepted', function () {
    $michael = owner();

    new SendStaffInvitation(author: $michael, staff: pam($michael, 'accepted'))->execute();
})->throws(InvalidArgumentException::class);

it('throws when the invitation was revoked', function () {
    $michael = owner();

    new SendStaffInvitation(author: $michael, staff: pam($michael, 'revoked'))->execute();
})->throws(InvalidArgumentException::class);

it('throws while the onboarding is pending', function () {
    $michael = owner();
    $michael->company->update(['is_onboarding_pending' => true]);

    new SendStaffInvitation(author: $michael, staff: pam($michael))->execute();
})->throws(InvalidArgumentException::class);

it('throws when no invitation was prepared', function () {
    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'kevin@dundermifflin.com']);

    new SendStaffInvitation(author: $michael, staff: $kevin)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author belongs to another company', function () {
    $michael = owner();
    $robert = owner();

    new SendStaffInvitation(author: $robert, staff: pam($michael))->execute();
})->throws(ModelNotFoundException::class);
