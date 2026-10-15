<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyStaffInvitation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyStaffInvitationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_withdraws_the_invitation(): void
    {
        Queue::fake();

        $michael = $this->owner();
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
    }

    #[Test]
    public function it_records_nothing_when_no_invitation_was_prepared(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $kevin = Staff::factory()->create(['company_id' => $michael->company_id]);

        new DestroyStaffInvitation(author: $michael, staff: $kevin)->execute();

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyStaffInvitation(author: $this->owner(), staff: Staff::factory()->create())->execute();
    }

    private function owner(): User
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

        return $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    }
}
