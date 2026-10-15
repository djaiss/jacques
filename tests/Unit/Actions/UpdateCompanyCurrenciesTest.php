<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateCompanyCurrencies;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateCompanyCurrenciesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_the_currencies_in_the_order_given(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        $result = new UpdateCompanyCurrencies(
            author: $michael,
            company: $company,
            currencies: ['USD', 'CAD', 'EUR'],
        )->execute();

        $this->assertInstanceOf(Company::class, $result);
        $this->assertEquals(['USD', 'CAD', 'EUR'], $company->refresh()->currencies);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyCurrenciesUpdated
                && $job->company->id === $company->id
                && $job->user->id === $michael->id
                && $job->parameters === ['currencies' => 'USD, CAD, EUR'],
        );
    }

    #[Test]
    public function it_refuses_an_empty_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        new UpdateCompanyCurrencies(
            author: $michael,
            company: $company,
            currencies: [],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_currency_the_catalogue_cannot_be_priced_in(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        new UpdateCompanyCurrencies(
            author: $michael,
            company: $company,
            currencies: ['CAD', 'SCHRUTEBUCK'],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_currency_listed_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        new UpdateCompanyCurrencies(
            author: $michael,
            company: $company,
            currencies: ['CAD', 'USD', 'CAD'],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_look_after_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $this->grant($dwight, PermissionEnum::EmployeeView);

        new UpdateCompanyCurrencies(
            author: $dwight,
            company: $company,
            currencies: ['CAD'],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $robert = User::factory()->create();
        $this->grant($robert, PermissionEnum::CompanyManage);

        new UpdateCompanyCurrencies(
            author: $robert,
            company: $company,
            currencies: ['CAD'],
        )->execute();
    }
}
