<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PrepareStaffInvitationTest;

use App\Actions\PrepareStaffInvitation;
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

it('prepares an invitation with a role', function () {
    Queue::fake();

    $michael = owner();
    $manager = role($michael, Role::MANAGER);
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'display_name' => null, 'email' => 'dwight@dundermifflin.com']);

    $invitation = new PrepareStaffInvitation(author: $michael, staff: $dwight, role: $manager)->execute();

    expect($invitation)->toBeInstanceOf(StaffInvitation::class);
    $this->assertDatabaseHas('staff_invitations', ['staff_id' => $dwight->id, 'role_id' => $manager->id, 'author_id' => $michael->id]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInvitationPrepared
            && $job->parameters === ['name' => 'Dwight Schrute', 'role' => $manager->name],
    );
});

it('changes the role of the invitation already prepared', function () {
    Queue::fake();

    $michael = owner();
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);
    StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => role($michael, Role::STAFF)->id]);
    $manager = role($michael, Role::MANAGER);

    new PrepareStaffInvitation(author: $michael, staff: $dwight, role: $manager)->execute();

    $this->assertDatabaseCount('staff_invitations', 1);
    expect($dwight->invitation()->value('role_id'))->toEqual($manager->id);
});

it('throws without an email address', function () {
    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => null]);

    new PrepareStaffInvitation(author: $michael, staff: $kevin, role: role($michael, Role::STAFF))->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member already has an account', function () {
    $michael = owner();
    $jim = Staff::factory()->create([
        'company_id' => $michael->company_id,
        'user_id' => User::factory()->create(['company_id' => $michael->company_id])->id,
        'email' => 'jim@dundermifflin.com',
    ]);

    new PrepareStaffInvitation(author: $michael, staff: $jim, role: role($michael, Role::STAFF))->execute();
})->throws(InvalidArgumentException::class);

it('throws when the role belongs to another company', function () {
    $michael = owner();
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);

    new PrepareStaffInvitation(author: $michael, staff: $dwight, role: Role::factory()->create())->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author may not manage roles', function () {
    $michael = owner();
    $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $michael->company_id]), Role::RECEPTIONIST);
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);

    new PrepareStaffInvitation(author: $pam, staff: $dwight, role: role($michael, Role::STAFF))->execute();
})->throws(ModelNotFoundException::class);

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function role(User $user, string $slug): Role
{
    return Role::query()->where('company_id', $user->company_id)->where('slug', $slug)->firstOrFail();
}
