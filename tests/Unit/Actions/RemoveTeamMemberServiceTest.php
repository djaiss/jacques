<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveTeamMemberService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveTeamMemberServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_team_member_performing_a_service_at_a_location(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = User::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locationServices()->attach($locationService->id);

        new RemoveTeamMemberService(author: $michael, user: $jim, locationService: $locationService)->execute();

        $this->assertDatabaseMissing('team_member_services', ['user_id' => $jim->id, 'location_service_id' => $locationService->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TeamMemberServiceRemoved,
        );
    }

    #[Test]
    public function it_throws_when_the_team_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new RemoveTeamMemberService(author: $michael, user: User::factory()->create(), locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_another_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $atStamford = LocationService::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
        $dwight->locations()->attach($scranton->id);
        $jim = User::factory()->create(['company_id' => $scranton->company_id]);

        new RemoveTeamMemberService(author: $dwight, user: $jim, locationService: $atStamford)->execute();
    }
}
