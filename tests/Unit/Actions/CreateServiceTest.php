<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateService;
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

class CreateServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_service_with_its_steps_at_the_end_of_its_category(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $colours = ServiceCategory::factory()->create(['company_id' => $company->id]);
        Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $colours->id, 'position' => 1]);

        $service = new CreateService(
            author: $michael,
            company: $company,
            name: ['en' => 'Colour'],
            price: 3500,
            steps: [
                ['active_duration' => 20, 'processing_duration' => 30],
                ['active_duration' => 15, 'processing_duration' => 0],
            ],
            description: ['en' => 'Roots and lengths'],
            category: $colours,
        )->execute();

        $this->assertInstanceOf(Service::class, $service);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'company_id' => $company->id,
            'service_category_id' => $colours->id,
            'price' => 3500,
            'is_active' => true,
            'position' => 2,
        ]);
        $this->assertEquals(['en' => 'Colour'], $service->refresh()->name);
        $this->assertEquals(['en' => 'Roots and lengths'], $service->description);
        $this->assertDatabaseHas('service_steps', ['service_id' => $service->id, 'position' => 1, 'active_duration' => 20, 'processing_duration' => 30]);
        $this->assertDatabaseHas('service_steps', ['service_id' => $service->id, 'position' => 2, 'active_duration' => 15, 'processing_duration' => 0]);
        $this->assertEquals(65, $service->duration);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCreated
                && $job->user->id === $michael->id
                && $job->parameters === ['name' => 'Colour'],
        );
    }

    #[Test]
    public function it_creates_a_service_without_a_category(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        $service = new CreateService(
            author: $michael,
            company: $company,
            name: ['en' => 'Beard trim'],
            price: 0,
            steps: [['active_duration' => 10, 'processing_duration' => 0]],
        )->execute();

        $this->assertNull($service->service_category_id);
        $this->assertEquals(1, $service->position);
    }

    #[Test]
    public function it_throws_without_a_step(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateService(author: $michael, company: $company, name: ['en' => 'Colour'], price: 3500, steps: [])->execute();
    }

    #[Test]
    public function it_throws_when_a_step_has_no_active_time(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateService(
            author: $michael,
            company: $company,
            name: ['en' => 'Colour'],
            price: 3500,
            steps: [['active_duration' => 0, 'processing_duration' => 30]],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_price_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateService(
            author: $michael,
            company: $company,
            name: ['en' => 'Colour'],
            price: -1,
            steps: [['active_duration' => 20, 'processing_duration' => 0]],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_company_already_has_a_service_of_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut']]);

        new CreateService(
            author: $michael,
            company: $company,
            name: ['en' => 'Haircut'],
            price: 3500,
            steps: [['active_duration' => 45, 'processing_duration' => 0]],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_category_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateService(
            author: $michael,
            company: $company,
            name: ['en' => 'Haircut'],
            price: 3500,
            steps: [['active_duration' => 45, 'processing_duration' => 0]],
            category: ServiceCategory::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        new CreateService(
            author: $dwight,
            company: $company,
            name: ['en' => 'Haircut'],
            price: 3500,
            steps: [['active_duration' => 45, 'processing_duration' => 0]],
        )->execute();
    }

    #[Test]
    public function it_writes_the_name_in_every_language_of_the_salon(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['languages' => ['fr', 'en']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        $result = new CreateService(author: $michael, company: $company, name: ['fr' => 'Coupe', 'en' => 'Haircut'], price: 3500, steps: [['active_duration' => 30, 'processing_duration' => 0]], description: ['fr' => 'Courte ou longue', 'en' => null])->execute();

        $this->assertEquals(['fr' => 'Coupe', 'en' => 'Haircut'], $result->refresh()->name);
        $this->assertEquals(['fr' => 'Courte ou longue', 'en' => null], $result->description);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->parameters === ['name' => 'Coupe'],
        );
    }

    #[Test]
    public function it_throws_when_the_name_is_missing_in_a_language_of_the_salon(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A name is needed in en');

        $company = Company::factory()->create(['languages' => ['fr', 'en']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateService(author: $michael, company: $company, name: ['fr' => 'Coupe', 'en' => ''], price: 3500, steps: [['active_duration' => 30, 'processing_duration' => 0]])->execute();
    }

    #[Test]
    public function it_throws_when_a_text_is_written_in_a_language_the_salon_does_not_use(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('es is not a language of the company');

        $company = Company::factory()->create(['languages' => ['en']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateService(author: $michael, company: $company, name: ['en' => 'Haircut', 'es' => 'Corte'], price: 3500, steps: [['active_duration' => 30, 'processing_duration' => 0]])->execute();
    }

    #[Test]
    public function it_allows_the_same_name_in_two_different_languages(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['languages' => ['en', 'fr']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Balayage', 'fr' => 'Mèches']]);

        $result = new CreateService(author: $michael, company: $company, name: ['en' => 'Highlights', 'fr' => 'Balayage'], price: 3500, steps: [['active_duration' => 30, 'processing_duration' => 0]])->execute();

        $this->assertEquals(['en' => 'Highlights', 'fr' => 'Balayage'], $result->name);
    }

    #[Test]
    public function it_throws_when_the_name_is_taken_in_the_same_language(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The company already has a service called Coupe');

        $company = Company::factory()->create(['languages' => ['en', 'fr']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut', 'fr' => 'Coupe']]);

        new CreateService(author: $michael, company: $company, name: ['en' => 'Trim', 'fr' => 'Coupe'], price: 3500, steps: [['active_duration' => 30, 'processing_duration' => 0]])->execute();
    }
}
