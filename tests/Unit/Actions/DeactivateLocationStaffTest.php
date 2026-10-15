<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AssignStaffLocationService;
use App\Actions\DeactivateLocationStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateLocationStaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_pauses_an_assignment_and_keeps_what_was_set_up_there(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create(['name' => 'Scranton']);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
        $jim->locations()->attach($scranton);
        $workingHour = StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::RoleManage);

        $staff = new DeactivateLocationStaff(author: $michael, staff: $jim, location: $scranton)->execute();

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertDatabaseHas('location_staff', [
            'staff_id' => $jim->id,
            'location_id' => $scranton->id,
            'is_active' => false,
        ]);
        $this->assertFalse($jim->activeLocations()->whereKey($scranton->id)->exists());
        $this->assertModelExists($workingHour);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationStaffDeactivated
                && $job->parameters === ['name' => 'Scranton', 'staff' => 'Jim Halpert'],
        );
    }

    #[Test]
    public function it_refuses_new_services_at_a_paused_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach($scranton);
        $haircut = LocationService::factory()->create(['location_id' => $scranton->id]);
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::OWNER);

        new DeactivateLocationStaff(author: $michael, staff: $jim, location: $scranton)->execute();

        new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $haircut)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $stamford = Location::factory()->create();
        $jim = Staff::factory()->create(['company_id' => $stamford->company_id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $stamford->company_id]), PermissionEnum::RoleManage);

        new DeactivateLocationStaff(author: $michael, staff: $jim, location: $stamford)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $scranton = Location::factory()->create();
        $karen = Staff::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::RoleManage);

        new DeactivateLocationStaff(author: $michael, staff: $karen, location: $scranton)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_decide_who_works_where(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $scranton = Location::factory()->create();
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach($scranton);
        $toby = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::StaffView);

        new DeactivateLocationStaff(author: $toby, staff: $jim, location: $scranton)->execute();
    }
}
