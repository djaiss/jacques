<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateStaff;
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

class ActivateStaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_back_a_staff_member_who_was_deactivated(): void
    {
        Queue::fake();

        $pam = Staff::factory()->inactive()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $michael = $this->grant(User::factory()->create(['company_id' => $pam->company_id]), PermissionEnum::StaffUpdate);

        $staff = new ActivateStaff(author: $michael, staff: $pam)->execute();

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertTrue($pam->refresh()->is_active);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffActivated
                && $job->parameters === ['name' => 'Pam Beesly'],
        );
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::StaffUpdate);

        new ActivateStaff(author: $michael, staff: Staff::factory()->inactive()->create())->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_staff_members(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $pam = Staff::factory()->inactive()->create();
        $toby = $this->grant(User::factory()->create(['company_id' => $pam->company_id]), PermissionEnum::StaffView);

        new ActivateStaff(author: $toby, staff: $pam)->execute();
    }
}
