<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveStaffLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveStaffLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_staff_member_performing_a_service_at_a_location(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locationServices()->attach($locationService->id);

        new RemoveStaffLocationService(author: $michael, staff: $jim, locationService: $locationService)->execute();

        $this->assertDatabaseMissing('staff_location_services', ['staff_id' => $jim->id, 'location_service_id' => $locationService->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffLocationServiceRemoved,
        );
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new RemoveStaffLocationService(author: $michael, staff: Staff::factory()->create(), locationService: $locationService)->execute();
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

        new RemoveStaffLocationService(author: $dwight, staff: $jim, locationService: $atStamford)->execute();
    }
}
