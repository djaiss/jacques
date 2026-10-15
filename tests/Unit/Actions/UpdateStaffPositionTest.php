<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateStaffPosition;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateStaffPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_staff_member_up_and_shifts_the_others_down(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $jim = Staff::factory()->create(['company_id' => $company->id, 'position' => 2]);
        $dwight = Staff::factory()->create(['company_id' => $company->id, 'position' => 3]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

        $staff = new UpdateStaffPosition(author: $michael, staff: $dwight, position: 1)->execute();

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertSame(1, $dwight->refresh()->position);
        $this->assertSame(2, $pam->refresh()->position);
        $this->assertSame(3, $jim->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffPositionUpdated,
        );
    }

    #[Test]
    public function it_moves_a_staff_member_down_and_shifts_the_others_up(): void
    {
        $company = Company::factory()->create();
        $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $jim = Staff::factory()->create(['company_id' => $company->id, 'position' => 2]);
        $dwight = Staff::factory()->create(['company_id' => $company->id, 'position' => 3]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

        new UpdateStaffPosition(author: $michael, staff: $pam, position: 3)->execute();

        $this->assertSame(3, $pam->refresh()->position);
        $this->assertSame(1, $jim->refresh()->position);
        $this->assertSame(2, $dwight->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_staff_members_of_another_company_alone(): void
    {
        $company = Company::factory()->create();
        $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $jim = Staff::factory()->create(['company_id' => $company->id, 'position' => 2]);
        $karen = Staff::factory()->create(['position' => 1]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

        new UpdateStaffPosition(author: $michael, staff: $jim, position: 1)->execute();

        $this->assertSame(2, $pam->refresh()->position);
        $this->assertSame(1, $karen->refresh()->position);
    }

    #[Test]
    public function it_throws_when_the_position_is_outside_the_team(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

        new UpdateStaffPosition(author: $michael, staff: $pam, position: 2)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::StaffUpdate);

        new UpdateStaffPosition(author: $michael, staff: Staff::factory()->create(['position' => 1]), position: 1)->execute();
    }
}
