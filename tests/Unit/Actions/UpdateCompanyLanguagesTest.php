<?php

declare(strict_types=1);

use App\Actions\UpdateCompanyLanguages;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('sets the languages in the order given', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    $result = new UpdateCompanyLanguages(
        author: $michael,
        company: $company,
        languages: ['fr', 'en', 'es'],
    )->execute();

    expect($result)->toBeInstanceOf(Company::class);
    expect($company->refresh()->languages)->toEqual(['fr', 'en', 'es']);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyLanguagesUpdated
            && $job->company->id === $company->id
            && $job->user->id === $michael->id
            && $job->parameters === ['languages' => 'fr, en, es'],
    );
});

it('refuses an empty list', function () {
    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    new UpdateCompanyLanguages(
        author: $michael,
        company: $company,
        languages: [],
    )->execute();
})->throws(InvalidArgumentException::class);

it('refuses a language the catalogue cannot be written in', function () {
    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    new UpdateCompanyLanguages(
        author: $michael,
        company: $company,
        languages: ['en', 'tlh'],
    )->execute();
})->throws(InvalidArgumentException::class);

it('refuses a language listed twice', function () {
    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    new UpdateCompanyLanguages(
        author: $michael,
        company: $company,
        languages: ['en', 'fr', 'en'],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author may not look after the company', function () {
    $company = Company::factory()->create();
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $this->grant($dwight, PermissionEnum::StaffView);

    new UpdateCompanyLanguages(
        author: $dwight,
        company: $company,
        languages: ['en'],
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author belongs to another company', function () {
    $company = Company::factory()->create();
    $robert = User::factory()->create();
    $this->grant($robert, PermissionEnum::CompanyManage);

    new UpdateCompanyLanguages(
        author: $robert,
        company: $company,
        languages: ['en'],
    )->execute();
})->throws(ModelNotFoundException::class);
