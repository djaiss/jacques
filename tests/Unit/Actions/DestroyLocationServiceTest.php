<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\TeamMemberService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_offering_a_service_and_forgets_who_performed_it_there(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create();
        $assignment = TeamMemberService::factory()->create(['location_service_id' => $locationService->id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new DestroyLocationService(author: $michael, locationService: $locationService)->execute();

        $this->assertModelMissing($locationService);
        $this->assertModelMissing($assignment);
        $this->assertModelExists($locationService->service);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationServiceDeleted,
        );
    }

    #[Test]
    public function it_throws_when_a_manager_changes_another_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $atStamford = LocationService::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
        $dwight->locations()->attach($scranton->id);

        new DestroyLocationService(author: $dwight, locationService: $atStamford)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new DestroyLocationService(author: $michael, locationService: LocationService::factory()->create())->execute();
    }
}
