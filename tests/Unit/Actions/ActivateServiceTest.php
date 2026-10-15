<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_activates_a_service(): void
    {
        Queue::fake();

        $service = Service::factory()->inactive()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new ActivateService(author: $michael, service: $service)->execute();

        $this->assertTrue($service->refresh()->is_active);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceActivated,
        );
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->inactive()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

        new ActivateService(author: $dwight, service: $service)->execute();
    }
}
