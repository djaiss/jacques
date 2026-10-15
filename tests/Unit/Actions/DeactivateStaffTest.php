<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateStaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deactivates_somebody_who_leaves_and_keeps_their_record(): void
    {
        Queue::fake();

        $pam = Staff::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $michael = $this->grant(User::factory()->create(['company_id' => $pam->company_id]), PermissionEnum::StaffUpdate);

        $staff = new DeactivateStaff(author: $michael, staff: $pam)->execute();

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertFalse($pam->refresh()->is_active);
        $this->assertModelExists($pam);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffDeactivated
                && $job->parameters === ['name' => 'Pam Beesly'],
        );
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::StaffUpdate);

        new DeactivateStaff(author: $michael, staff: Staff::factory()->create())->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_staff_members(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $pam = Staff::factory()->create();
        $toby = $this->grant(User::factory()->create(['company_id' => $pam->company_id]), PermissionEnum::StaffView);

        new DeactivateStaff(author: $toby, staff: $pam)->execute();
    }
}
