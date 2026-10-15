<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyCatalogue;
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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyCatalogueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_every_category_and_service_of_the_company_only(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
        Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id]);
        Service::factory()->create(['company_id' => $company->id]);
        $elsewhere = Service::factory()->create();

        new DestroyCatalogue(author: $michael, company: $company)->execute();

        $this->assertEquals(0, Service::query()->where('company_id', $company->id)->count());
        $this->assertModelMissing($cuts);
        $this->assertModelExists($elsewhere);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CatalogueDestroyed,
        );
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new DestroyCatalogue(author: $michael, company: Company::factory()->create())->execute();
    }
}
