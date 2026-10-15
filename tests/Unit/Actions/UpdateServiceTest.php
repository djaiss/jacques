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

        $service = Service::factory()->create(['name' => ['en' => 'Colour'], 'prices' => ['CAD' => 3500]]);
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        $service = new UpdateService(
            author: $michael,
            service: $service,
            name: ['en' => 'Full colour'],
            prices: ['CAD' => 4200],
            steps: [
                ['duration' => 25, 'requires_staff' => true], ['duration' => 35, 'requires_staff' => false],
                ['duration' => 10, 'requires_staff' => true],
            ],
        )->execute();

        $service->refresh();

        $this->assertEquals(['en' => 'Full colour'], $service->name);
        $this->assertEquals(['CAD' => 4200], $service->prices);
        $this->assertNull($service->description);
        $this->assertCount(3, $service->steps);
        $this->assertEquals(70, $service->duration);
        $this->assertEquals(35, $service->free_duration);

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
            prices: ['CAD' => 1500],
            steps: [['duration' => 15, 'requires_staff' => true]],
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

        new UpdateService(author: $michael, service: $service, name: ['en' => 'Colour'], prices: ['CAD' => 3500], steps: [])->execute();
    }

    #[Test]
    public function it_throws_when_another_service_has_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = Service::factory()->create();
        Service::factory()->create(['company_id' => $service->company_id, 'name' => ['en' => 'Haircut']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

        new UpdateService(
            author: $michael,
            service: $service,
            name: ['en' => 'Haircut'],
            prices: ['CAD' => 3500],
            steps: [['duration' => 45, 'requires_staff' => true]],
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
            name: ['en' => 'Haircut'],
            prices: ['CAD' => 3500],
            steps: [['duration' => 45, 'requires_staff' => true]],
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
            name: ['en' => 'Haircut'],
            prices: ['CAD' => 3500],
            steps: [['duration' => 45, 'requires_staff' => true]],
        )->execute();
    }

    #[Test]
    public function it_keeps_the_texts_of_a_language_the_salon_stopped_using(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['languages' => ['en', 'es']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'name' => ['en' => 'Haircut', 'es' => 'Corte'],
            'description' => ['en' => 'Short or long', 'es' => 'Corto o largo'],
        ]);
        $company->update(['languages' => ['en']]);

        new UpdateService(
            author: $michael,
            service: $service->refresh(),
            name: ['en' => 'Women\'s haircut'],
            prices: ['CAD' => 3500],
            steps: [['duration' => 45, 'requires_staff' => true]],
        )->execute();

        $company->update(['languages' => ['en', 'es']]);
        $service->refresh();

        $this->assertEquals(['es' => 'Corte', 'en' => 'Women\'s haircut'], $service->name);
        $this->assertEquals(['es' => 'Corto o largo'], $service->description);
        $this->assertEquals(['en'], $service->missingLanguages());
    }

    #[Test]
    public function it_shows_a_language_added_to_the_salon_as_missing_until_it_is_written(): void
    {
        $company = Company::factory()->create(['languages' => ['en']]);
        $service = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut']]);

        $company->update(['languages' => ['en', 'fr']]);

        $this->assertEquals(['en' => 'Haircut'], $service->refresh()->name);
        $this->assertEquals(['fr'], $service->missingLanguages());
    }

    #[Test]
    public function it_keeps_the_prices_of_a_currency_the_salon_stopped_using(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['currencies' => ['CAD', 'USD']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'name' => ['en' => 'Haircut'],
            'prices' => ['CAD' => 3500, 'USD' => 2600],
        ]);
        $company->update(['currencies' => ['CAD']]);

        new UpdateService(
            author: $michael,
            service: $service->refresh(),
            name: ['en' => 'Haircut'],
            prices: ['CAD' => 4000],
            steps: [['duration' => 30, 'requires_staff' => true]],
        )->execute();

        $this->assertEquals(['USD' => 2600, 'CAD' => 4000], $service->refresh()->prices);
        $this->assertEquals([], $service->missingCurrencies());
    }

    #[Test]
    public function it_shows_a_currency_added_to_the_salon_as_missing_until_it_is_priced(): void
    {
        $company = Company::factory()->create(['currencies' => ['CAD']]);
        $service = Service::factory()->create(['company_id' => $company->id, 'prices' => ['CAD' => 3500]]);

        $company->update(['currencies' => ['CAD', 'USD']]);

        $this->assertEquals(['CAD' => 3500], $service->refresh()->prices);
        $this->assertEquals(['USD'], $service->missingCurrencies());
    }
}
