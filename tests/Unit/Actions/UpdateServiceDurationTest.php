<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateServiceDuration;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateServiceDurationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_duration_of_a_one_step_service(): void
    {
        Queue::fake();

        $service = Service::factory()->create(['name' => ['en' => 'Beard trim']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        $result = new UpdateServiceDuration(author: $michael, service: $service, duration: 20)->execute();

        $this->assertInstanceOf(Service::class, $result);
        $this->assertEquals(20, $service->refresh()->duration);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceDurationUpdated
                && $job->parameters === ['name' => 'Beard trim'],
        );
    }

    #[Test]
    public function it_throws_for_a_service_made_of_several_steps(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2]);
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateServiceDuration(author: $michael, service: $service, duration: 20)->execute();
    }

    #[Test]
    public function it_throws_for_a_duration_below_one_minute(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateServiceDuration(author: $michael, service: $service, duration: 0)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new UpdateServiceDuration(author: $michael, service: $service, duration: 20)->execute();
    }
}
