<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AssignStaffLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\LocationStaff;
use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignStaffLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_a_staff_member_perform_a_service_at_their_location(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create(['name' => 'Scranton']);
        $haircut = Service::factory()->create(['company_id' => $scranton->company_id, 'name' => ['en' => 'Haircut']]);
        $locationService = LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
        $jim->locations()->attach($scranton->id);

        $result = new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();

        $this->assertInstanceOf(Staff::class, $result);
        $this->assertDatabaseHas('staff_location_services', ['staff_id' => $jim->id, 'location_service_id' => $locationService->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffLocationServiceAssigned
                && $job->parameters === [
                    'name' => 'Haircut',
                    'location' => 'Scranton',
                    'staff' => 'Jim Halpert',
                ],
        );
    }

    #[Test]
    public function it_changes_nothing_when_the_staff_member_already_performs_it(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locations()->attach($locationService->location_id);
        $jim->locationServices()->attach($locationService->id);

        new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();

        $this->assertCount(1, $jim->locationServices()->get());
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);

        new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_the_assignment_of_the_staff_member_to_the_location_is_paused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A staff member can only perform a service at a location where their assignment is active');

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
        LocationStaff::factory()->paused()->create(['location_id' => $locationService->location_id, 'staff_id' => $jim->id]);

        new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_does_not_offer_the_service_at_the_moment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create(['is_active' => false]);
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locations()->attach($locationService->location_id);

        new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_the_service_is_deactivated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create();
        $locationService->service->update(['is_active' => false]);
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locations()->attach($locationService->location_id);

        new AssignStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new AssignStaffLocationService(author: $michael, staff: Staff::factory()->create(), locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_another_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $atStamford = LocationService::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
        Staff::factory()->create(['company_id' => $scranton->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach($atStamford->location_id);

        new AssignStaffLocationService(author: $dwight, staff: $jim, locationService: $atStamford)->execute();
    }
}
