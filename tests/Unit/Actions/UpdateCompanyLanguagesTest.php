<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateCompanyLanguages;
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

class UpdateCompanyLanguagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_the_languages_in_the_order_given(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        $result = new UpdateCompanyLanguages(
            author: $michael,
            company: $company,
            languages: ['fr', 'en', 'es'],
        )->execute();

        $this->assertInstanceOf(Company::class, $result);
        $this->assertEquals(['fr', 'en', 'es'], $company->refresh()->languages);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyLanguagesUpdated
                && $job->company->id === $company->id
                && $job->user->id === $michael->id
                && $job->parameters === ['languages' => 'fr, en, es'],
        );
    }

    #[Test]
    public function it_refuses_an_empty_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        new UpdateCompanyLanguages(
            author: $michael,
            company: $company,
            languages: [],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_language_the_catalogue_cannot_be_written_in(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        new UpdateCompanyLanguages(
            author: $michael,
            company: $company,
            languages: ['en', 'tlh'],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_language_listed_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $this->grant($michael, PermissionEnum::CompanyManage);

        new UpdateCompanyLanguages(
            author: $michael,
            company: $company,
            languages: ['en', 'fr', 'en'],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_look_after_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $this->grant($dwight, PermissionEnum::StaffView);

        new UpdateCompanyLanguages(
            author: $dwight,
            company: $company,
            languages: ['en'],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $robert = User::factory()->create();
        $this->grant($robert, PermissionEnum::CompanyManage);

        new UpdateCompanyLanguages(
            author: $robert,
            company: $company,
            languages: ['en'],
        )->execute();
    }
}
