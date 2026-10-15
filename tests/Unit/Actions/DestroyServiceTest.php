<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\TeamMemberService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_service_with_its_steps_offers_and_team_members(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create();
        $service = $locationService->service;
        $assignment = TeamMemberService::factory()->create(['location_service_id' => $locationService->id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new DestroyService(author: $michael, service: $service)->execute();

        $this->assertModelMissing($service);
        $this->assertModelMissing($locationService);
        $this->assertModelMissing($assignment);
        $this->assertDatabaseMissing('service_steps', ['service_id' => $service->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceDeleted
                && $job->parameters === ['name' => $service->name],
        );
    }

    #[Test]
    public function it_moves_up_the_services_after_it(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $trim = Service::factory()->create(['company_id' => $company->id, 'position' => 2]);

        new DestroyService(author: $michael, service: $shave)->execute();

        $this->assertEquals(1, $trim->refresh()->position);
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

        new DestroyService(author: $dwight, service: $service)->execute();
    }

    #[Test]
    public function it_throws_when_the_service_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new DestroyService(author: $michael, service: Service::factory()->create())->execute();
    }
}
