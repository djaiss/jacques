<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\DestroyStaffInvitationTest;

use App\Actions\DestroyStaffInvitation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('withdraws the invitation', function () {
    Queue::fake();

    $michael = owner();
    $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'display_name' => null]);
    $invitation = StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => Role::query()->where('company_id', $michael->company_id)->value('id')]);

    new DestroyStaffInvitation(author: $michael, staff: $dwight)->execute();

    $this->assertModelMissing($invitation);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInvitationDeleted
            && $job->parameters === ['name' => 'Dwight Schrute'],
    );
});

it('records nothing when no invitation was prepared', function () {
    Queue::fake();

    $michael = owner();
    $kevin = Staff::factory()->create(['company_id' => $michael->company_id]);

    new DestroyStaffInvitation(author: $michael, staff: $kevin)->execute();

    Queue::assertNotPushed(LogUserAction::class);
});

it('throws when the staff member belongs to another company', function () {
    new DestroyStaffInvitation(author: owner(), staff: Staff::factory()->create())->execute();
})->throws(ModelNotFoundException::class);

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}
