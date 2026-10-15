<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreDefaultCatalogue;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreDefaultCatalogueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_back_the_example_catalogue_even_after_it_was_created_once(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['default_catalogue_created_at' => now()]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new RestoreDefaultCatalogue(author: $michael, company: $company)->execute();

        $this->assertEquals(6, ServiceCategory::query()->where('company_id', $company->id)->count());
        $this->assertEquals(17, Service::query()->where('company_id', $company->id)->count());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CatalogueRestored,
        );
    }

    #[Test]
    public function it_throws_when_the_catalogue_is_not_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        ServiceCategory::factory()->create(['company_id' => $company->id]);

        new RestoreDefaultCatalogue(author: $michael, company: $company)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new RestoreDefaultCatalogue(author: $michael, company: Company::factory()->create())->execute();
    }
}
