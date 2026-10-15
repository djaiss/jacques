<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyServiceCategory;
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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_category_and_keeps_its_services_without_a_category(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Colours'], 'position' => 1]);
        $care = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
        $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $colour = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $colours->id, 'position' => 1]);

        new DestroyServiceCategory(author: $michael, category: $colours)->execute();

        $this->assertModelMissing($colours);
        $this->assertDatabaseHas('services', ['id' => $colour->id, 'service_category_id' => null, 'position' => 2]);
        $this->assertEquals(1, $shave->refresh()->position);
        $this->assertEquals(1, $care->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryDeleted
                && $job->parameters === ['name' => 'Colours'],
        );
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $category = ServiceCategory::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $category->company_id]), Role::MANAGER);

        new DestroyServiceCategory(author: $dwight, category: $category)->execute();
    }

    #[Test]
    public function it_throws_when_the_category_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new DestroyServiceCategory(author: $michael, category: ServiceCategory::factory()->create())->execute();
    }
}
