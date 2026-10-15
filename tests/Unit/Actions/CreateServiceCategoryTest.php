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
            name: ['en' => '<b>Colours</b>'],
            description: ['en' => 'Everything that changes the colour'],
        )->execute();

        $this->assertInstanceOf(ServiceCategory::class, $category);
        $category->refresh();

        $this->assertEquals($company->id, $category->company_id);
        $this->assertEquals(['en' => 'Colours'], $category->name);
        $this->assertEquals(['en' => 'Everything that changes the colour'], $category->description);
        $this->assertEquals(2, $category->position);

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

        new CreateServiceCategory(author: $michael, company: $company, name: ['en' => '  '])->execute();
    }

    #[Test]
    public function it_throws_when_the_company_already_has_a_category_of_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Cuts']]);

        new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Cuts'])->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_the_catalogue(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        new CreateServiceCategory(author: $dwight, company: $company, name: ['en' => 'Cuts'])->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new CreateServiceCategory(author: $michael, company: Company::factory()->create(), name: ['en' => 'Cuts'])->execute();
    }

    #[Test]
    public function it_writes_the_name_in_every_language_of_the_salon(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['languages' => ['fr', 'en']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        $result = new CreateServiceCategory(author: $michael, company: $company, name: ['fr' => 'Coupe', 'en' => 'Haircut'], description: ['fr' => 'Courte ou longue', 'en' => null])->execute();

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

        new CreateServiceCategory(author: $michael, company: $company, name: ['fr' => 'Coupe', 'en' => ''])->execute();
    }

    #[Test]
    public function it_throws_when_a_text_is_written_in_a_language_the_salon_does_not_use(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('es is not a language of the company');

        $company = Company::factory()->create(['languages' => ['en']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

        new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Haircut', 'es' => 'Corte'])->execute();
    }

    #[Test]
    public function it_allows_the_same_name_in_two_different_languages(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['languages' => ['en', 'fr']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Balayage', 'fr' => 'Mèches']]);

        $result = new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Highlights', 'fr' => 'Balayage'])->execute();

        $this->assertEquals(['en' => 'Highlights', 'fr' => 'Balayage'], $result->name);
    }

    #[Test]
    public function it_throws_when_the_name_is_taken_in_the_same_language(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The company already has a service category called Coupe');

        $company = Company::factory()->create(['languages' => ['en', 'fr']]);
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut', 'fr' => 'Coupe']]);

        new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Trim', 'fr' => 'Coupe'])->execute();
    }
}
