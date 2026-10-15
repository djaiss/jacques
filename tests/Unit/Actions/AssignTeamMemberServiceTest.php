<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AssignTeamMemberService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignTeamMemberServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_a_team_member_perform_a_service_at_their_location(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create(['name' => 'Scranton']);
        $haircut = Service::factory()->create(['company_id' => $scranton->company_id, 'name' => 'Haircut']);
        $locationService = LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $haircut->id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);
        $jim = User::factory()->create(['company_id' => $scranton->company_id, 'email' => 'jim.halpert@dundermifflin.com']);
        $jim->locations()->attach($scranton->id);

        $result = new AssignTeamMemberService(author: $michael, user: $jim, locationService: $locationService)->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('team_member_services', ['user_id' => $jim->id, 'location_service_id' => $locationService->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TeamMemberServiceAssigned
                && $job->parameters === [
                    'name' => 'Haircut',
                    'location' => 'Scranton',
                    'email' => 'jim.halpert@dundermifflin.com',
                ],
        );
    }

    #[Test]
    public function it_changes_nothing_when_the_team_member_already_performs_it(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = User::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locations()->attach($locationService->location_id);
        $jim->locationServices()->attach($locationService->id);

        new AssignTeamMemberService(author: $michael, user: $jim, locationService: $locationService)->execute();

        $this->assertCount(1, $jim->locationServices()->get());
    }

    #[Test]
    public function it_throws_when_the_team_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = User::factory()->create(['company_id' => $locationService->location->company_id]);

        new AssignTeamMemberService(author: $michael, user: $jim, locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_does_not_offer_the_service_at_the_moment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create(['is_active' => false]);
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = User::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locations()->attach($locationService->location_id);

        new AssignTeamMemberService(author: $michael, user: $jim, locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_the_service_is_deactivated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create();
        $locationService->service->update(['is_active' => false]);
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);
        $jim = User::factory()->create(['company_id' => $locationService->location->company_id]);
        $jim->locations()->attach($locationService->location_id);

        new AssignTeamMemberService(author: $michael, user: $jim, locationService: $locationService)->execute();
    }

    #[Test]
    public function it_throws_when_the_team_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new AssignTeamMemberService(author: $michael, user: User::factory()->create(), locationService: $locationService)->execute();
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
        $jim->locations()->attach($atStamford->location_id);

        new AssignTeamMemberService(author: $dwight, user: $jim, locationService: $atStamford)->execute();
    }
}
