<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateServiceCategory;
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

class CreateServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_service_category_after_the_existing_ones(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);

        $category = new CreateServiceCategory(
            author: $michael,
            company: $company,
            name: '<b>Colours</b>',
            description: 'Everything that changes the colour',
        )->execute();

        $this->assertInstanceOf(ServiceCategory::class, $category);
        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'company_id' => $company->id,
            'name' => 'Colours',
            'description' => 'Everything that changes the colour',
            'position' => 2,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryCreated
                && $job->user->id === $michael->id
                && $job->parameters === ['name' => 'Colours'],
        );
    }

    #[Test]
    public function it_throws_when_the_name_is_blank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateServiceCategory(author: $michael, company: $company, name: '  ')->execute();
    }

    #[Test]
    public function it_throws_when_the_company_already_has_a_category_of_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => 'Cuts']);

        new CreateServiceCategory(author: $michael, company: $company, name: 'Cuts')->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        new CreateServiceCategory(author: $dwight, company: $company, name: 'Cuts')->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new CreateServiceCategory(author: $michael, company: Company::factory()->create(), name: 'Cuts')->execute();
    }
}
