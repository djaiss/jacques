<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateLocationStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateLocationStaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resumes_a_paused_assignment(): void
    {
        Queue::fake();

        $stamford = Location::factory()->create();
        $andy = Staff::factory()->create(['company_id' => $stamford->company_id]);
        $assignment = LocationStaff::factory()->paused()->create(['location_id' => $stamford->id, 'staff_id' => $andy->id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $stamford->company_id]), PermissionEnum::RoleManage);

        $staff = new ActivateLocationStaff(author: $michael, staff: $andy, location: $stamford)->execute();

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertTrue($assignment->refresh()->is_active);
        $this->assertTrue($staff->activeLocations()->whereKey($stamford->id)->exists());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationStaffActivated,
        );
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $stamford = Location::factory()->create();
        $andy = Staff::factory()->create(['company_id' => $stamford->company_id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $stamford->company_id]), PermissionEnum::RoleManage);

        new ActivateLocationStaff(author: $michael, staff: $andy, location: $stamford)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $andy = Staff::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $andy->company_id]), PermissionEnum::RoleManage);

        new ActivateLocationStaff(author: $michael, staff: $andy, location: Location::factory()->create())->execute();
    }
}
