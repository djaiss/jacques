<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\RevokeStaffInvitationTest;

use App\Actions\RevokeStaffInvitation;
use App\Enums\StaffInvitationStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function kevin(User $owner): Staff
{
    return Staff::factory()->create(['company_id' => $owner->company_id, 'first_name' => 'Kevin', 'last_name' => 'Malone', 'display_name' => null, 'email' => 'kevin@dundermifflin.com']);
}

it('revokes a pending invitation', function () {
    Queue::fake();

    $michael = owner();
    $kevin = kevin($michael);
    StaffInvitation::factory()->sent()->create(['staff_id' => $kevin->id]);

    $invitation = new RevokeStaffInvitation(author: $michael, staff: $kevin)->execute();

    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Revoked);
    $this->assertDatabaseHas('staff_invitations', ['staff_id' => $kevin->id, 'revoked_at' => $invitation->revoked_at]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInvitationRevoked
            && $job->parameters === ['name' => 'Kevin Malone'],
    );
});

it('throws when the invitation was never sent', function () {
    $michael = owner();
    $kevin = kevin($michael);
    StaffInvitation::factory()->create(['staff_id' => $kevin->id]);

    new RevokeStaffInvitation(author: $michael, staff: $kevin)->execute();
})->throws(InvalidArgumentException::class);

it('throws when there is no invitation', function () {
    $michael = owner();

    new RevokeStaffInvitation(author: $michael, staff: kevin($michael))->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author belongs to another company', function () {
    $michael = owner();
    $kevin = kevin($michael);
    StaffInvitation::factory()->sent()->create(['staff_id' => $kevin->id]);

    new RevokeStaffInvitation(author: owner(), staff: $kevin)->execute();
})->throws(ModelNotFoundException::class);
