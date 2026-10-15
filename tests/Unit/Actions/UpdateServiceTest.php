<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_a_service_and_replaces_its_steps(): void
    {
        Queue::fake();

        $service = Service::factory()->create(['name' => 'Colour', 'price' => 3500]);
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        $service = new UpdateService(
            author: $michael,
            service: $service,
            name: 'Full colour',
            price: 4200,
            steps: [
                ['active_duration' => 25, 'processing_duration' => 35],
                ['active_duration' => 10, 'processing_duration' => 0],
            ],
        )->execute();

        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Full colour', 'price' => 4200, 'description' => null]);
        $this->assertCount(2, $service->steps);
        $this->assertEquals(70, $service->duration);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceUpdated
                && $job->parameters === ['name' => 'Full colour'],
        );
    }

    #[Test]
    public function it_moves_a_service_to_the_end_of_its_new_category_and_closes_the_gap(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id]);
        $fringe = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 1]);
        $haircut = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 2]);
        Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $colours->id, 'position' => 1]);

        new UpdateService(
            author: $michael,
            service: $fringe,
            name: $fringe->name,
            price: 1500,
            steps: [['active_duration' => 15, 'processing_duration' => 0]],
            category: $colours,
        )->execute();

        $this->assertDatabaseHas('services', ['id' => $fringe->id, 'service_category_id' => $colours->id, 'position' => 2]);
        $this->assertEquals(1, $haircut->refresh()->position);
    }

    #[Test]
    public function it_throws_without_a_step(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateService(author: $michael, service: $service, name: 'Colour', price: 3500, steps: [])->execute();
    }

    #[Test]
    public function it_throws_when_another_service_has_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        Service::factory()->create(['company_id' => $service->company_id, 'name' => 'Haircut']);
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateService(
            author: $michael,
            service: $service,
            name: 'Haircut',
            price: 3500,
            steps: [['active_duration' => 45, 'processing_duration' => 0]],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_category_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateService(
            author: $michael,
            service: $service,
            name: 'Haircut',
            price: 3500,
            steps: [['active_duration' => 45, 'processing_duration' => 0]],
            category: ServiceCategory::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

        new UpdateService(
            author: $dwight,
            service: $service,
            name: 'Haircut',
            price: 3500,
            steps: [['active_duration' => 45, 'processing_duration' => 0]],
        )->execute();
    }
}
