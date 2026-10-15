<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SyncStaffLocationServices;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncStaffLocationServicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_the_services_a_staff_member_performs_at_a_location(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create(['name' => 'Scranton']);
        $beardTrim = $this->offer($scranton);
        $shave = $this->offer($scranton);
        $haircut = $this->offer($scranton);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
        $jim->locations()->attach($scranton->id);
        $jim->locationServices()->attach($haircut->id);

        $result = new SyncStaffLocationServices(
            author: $michael,
            staff: $jim,
            location: $scranton,
            locationServiceIds: [$beardTrim->id, $shave->id],
        )->execute();

        $this->assertInstanceOf(Staff::class, $result);
        $this->assertEqualsCanonicalizing([$beardTrim->id, $shave->id], $jim->locationServices()->pluck('location_services.id')->all());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffServicesUpdated
                && $job->parameters === ['name' => 'Jim Halpert', 'location' => 'Scranton'],
        );
    }

    #[Test]
    public function it_keeps_the_services_performed_at_another_location(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create();
        $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
        $atStamford = $this->offer($stamford);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach([$scranton->id, $stamford->id]);
        $jim->locationServices()->attach($atStamford->id);

        new SyncStaffLocationServices(author: $michael, staff: $jim, location: $scranton, locationServiceIds: [])->execute();

        $this->assertEquals([$atStamford->id], $jim->locationServices()->pluck('location_services.id')->all());
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);

        new SyncStaffLocationServices(author: $michael, staff: $jim, location: $scranton, locationServiceIds: [])->execute();
    }

    #[Test]
    public function it_throws_when_a_service_is_not_offered_at_the_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $scranton = Location::factory()->create();
        $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
        $atStamford = $this->offer($stamford);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach($scranton->id);

        new SyncStaffLocationServices(author: $michael, staff: $jim, location: $scranton, locationServiceIds: [$atStamford->id])->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);

        new SyncStaffLocationServices(author: $michael, staff: Staff::factory()->create(), location: $scranton, locationServiceIds: [])->execute();
    }

    private function offer(Location $location): LocationService
    {
        $service = Service::factory()->create(['company_id' => $location->company_id]);

        return LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $service->id]);
    }
}
