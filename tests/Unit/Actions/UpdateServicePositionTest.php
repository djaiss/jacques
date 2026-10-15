<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateServicePosition;
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

class UpdateServicePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_service_within_its_category_only(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
        $haircut = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 1]);
        $fringe = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 2]);
        $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);

        new UpdateServicePosition(author: $michael, service: $fringe, position: 1)->execute();

        $this->assertEquals([$fringe->id, $haircut->id], $cuts->services()->pluck('id')->all());
        $this->assertEquals(1, $shave->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceMoved,
        );
    }

    #[Test]
    public function it_moves_a_service_without_a_category_among_the_others_without_one(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $trim = Service::factory()->create(['company_id' => $company->id, 'position' => 2]);

        new UpdateServicePosition(author: $michael, service: $shave, position: 2)->execute();

        $this->assertEquals(2, $shave->refresh()->position);
        $this->assertEquals(1, $trim->refresh()->position);
    }

    #[Test]
    public function it_throws_when_the_position_is_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateServicePosition(author: $michael, service: $service, position: 0)->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $service = Service::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

        new UpdateServicePosition(author: $dwight, service: $service, position: 1)->execute();
    }
}
