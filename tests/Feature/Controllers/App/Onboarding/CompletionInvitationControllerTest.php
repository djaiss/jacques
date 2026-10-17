<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\CompletionInvitationControllerTest;

use App\Enums\StaffInvitationStatusEnum;
use App\Jobs\SendEmail;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

it('sends every prepared invitation', function () {
    Queue::fake();

    $michael = owner();
    $role = Role::query()->where('company_id', $michael->company_id)->where('slug', Role::STAFF)->firstOrFail();
    $jim = StaffInvitation::factory()->create(['role_id' => $role->id, 'staff_id' => Staff::factory()->create(['company_id' => $michael->company_id])->id]);
    $pam = StaffInvitation::factory()->create(['role_id' => $role->id, 'staff_id' => Staff::factory()->create(['company_id' => $michael->company_id])->id]);

    $response = $this->actingAs($michael)->post(route('onboarding.completion.invitations.create'));

    $response->assertRedirect(route('dashboard.index'));
    $response->assertSessionHas('status', '2 invitations are on their way.');
    expect($jim->refresh()->status())->toBe(StaffInvitationStatusEnum::Pending);
    expect($pam->refresh()->status())->toBe(StaffInvitationStatusEnum::Pending);
    Queue::assertPushed(SendEmail::class, 2);
});

it('leaves the invitations of another company alone', function () {
    Queue::fake();

    $michael = owner();
    $toby = StaffInvitation::factory()->create();

    $this->actingAs($michael)->post(route('onboarding.completion.invitations.create'));

    expect($toby->refresh()->status())->toBe(StaffInvitationStatusEnum::Prepared);
    Queue::assertNotPushed(SendEmail::class);
});
