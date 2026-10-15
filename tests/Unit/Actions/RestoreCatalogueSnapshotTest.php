<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyServiceCategory;
use App\Actions\RestoreCatalogueSnapshot;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\CatalogueSnapshot;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreCatalogueSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_category_back_in_its_place_with_its_services_and_steps(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Cuts'], 'position' => 1]);
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Colours'], 'position' => 2]);
        ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Care'], 'position' => 3]);
        $colour = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Full Color'], 'prices' => ['CAD' => 10000]]);
        ServiceStep::factory()->create(['service_id' => $colour->id, 'position' => 2, 'name' => ['en' => 'Processing'], 'requires_staff' => false]);

        $snapshot = CatalogueSnapshot::ofCategory($colours->load('services.steps'));
        new DestroyServiceCategory(author: $michael, category: $colours, withServices: true)->execute();

        new RestoreCatalogueSnapshot(author: $michael, company: $company, snapshot: $snapshot)->execute();

        $this->assertEquals(['Cuts', 'Colours', 'Care'], ServiceCategory::query()->where('company_id', $company->id)->orderBy('position')->get()->map(fn (ServiceCategory $category): string => $category->name['en'])->all());

        $restored = Service::query()->where('company_id', $company->id)->sole();

        $this->assertEquals(['CAD' => 10000], $restored->prices);
        $this->assertEquals(60, $restored->duration);
        $this->assertEquals(['en' => 'Processing'], $restored->steps[1]->name);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CatalogueRemovalRestored,
        );
    }

    #[Test]
    public function it_throws_when_the_category_of_a_service_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $elsewhere = ServiceCategory::factory()->create();
        $service = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $elsewhere->id]);

        new RestoreCatalogueSnapshot(author: $michael, company: $company, snapshot: CatalogueSnapshot::ofService($service))->execute();
    }
}
