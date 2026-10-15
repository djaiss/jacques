<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateServiceCategoryPosition;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateServiceCategoryPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_category_up_and_shifts_the_ones_it_passes(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $cuts = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
        $care = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 3]);

        new UpdateServiceCategoryPosition(author: $michael, category: $care, position: 1)->execute();

        $this->assertEquals([$care->id, $cuts->id, $colours->id], $company->serviceCategories()->pluck('id')->all());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryMoved,
        );
    }

    #[Test]
    public function it_moves_a_category_down_and_shifts_the_ones_it_passes(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $cuts = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
        $care = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 3]);

        new UpdateServiceCategoryPosition(author: $michael, category: $cuts, position: 3)->execute();

        $this->assertEquals([$colours->id, $care->id, $cuts->id], $company->serviceCategories()->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $company->serviceCategories()->pluck('position')->all());
    }

    #[Test]
    public function it_throws_when_the_position_is_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $category = ServiceCategory::factory()->create(['position' => 1]);
        $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

        new UpdateServiceCategoryPosition(author: $michael, category: $category, position: 2)->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $category = ServiceCategory::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $category->company_id]), Role::MANAGER);

        new UpdateServiceCategoryPosition(author: $dwight, category: $category, position: 1)->execute();
    }
}
