<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateServiceCategory;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_a_service_category(): void
    {
        Queue::fake();

        $category = ServiceCategory::factory()->create(['name' => 'Cuts']);
        $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

        $category = new UpdateServiceCategory(author: $michael, category: $category, name: 'Haircuts')->execute();

        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'name' => 'Haircuts',
            'description' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryUpdated
                && $job->parameters === ['name' => 'Haircuts'],
        );
    }

    #[Test]
    public function it_keeps_its_own_name(): void
    {
        Queue::fake();

        $category = ServiceCategory::factory()->create(['name' => 'Cuts']);
        $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

        new UpdateServiceCategory(author: $michael, category: $category, name: 'Cuts', description: 'Short and long')->execute();

        $this->assertEquals('Short and long', $category->refresh()->description);
    }

    #[Test]
    public function it_throws_when_another_category_has_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $category = ServiceCategory::factory()->create(['name' => 'Cuts']);
        ServiceCategory::factory()->create(['company_id' => $category->company_id, 'name' => 'Colours']);
        $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

        new UpdateServiceCategory(author: $michael, category: $category, name: 'Colours')->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $category = ServiceCategory::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $category->company_id]), Role::MANAGER);

        new UpdateServiceCategory(author: $dwight, category: $category, name: 'Cuts')->execute();
    }

    #[Test]
    public function it_throws_when_the_category_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new UpdateServiceCategory(author: $michael, category: ServiceCategory::factory()->create(), name: 'Cuts')->execute();
    }
}
