<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deactivates_a_service_and_keeps_where_it_was_offered(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create();
        $service = $locationService->service;
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new DeactivateService(author: $michael, service: $service)->execute();

        $this->assertFalse($service->refresh()->is_active);
        $this->assertModelExists($locationService);
        $this->assertCount(1, $service->steps);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceDeactivated
                && $job->parameters === ['name' => $service->name['en']],
        );
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

        new DeactivateService(author: $dwight, service: $service)->execute();
    }

    #[Test]
    public function it_throws_when_the_service_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new DeactivateService(author: $michael, service: Service::factory()->create())->execute();
    }
}
