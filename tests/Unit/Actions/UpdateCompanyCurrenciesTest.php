<?php

declare(strict_types=1);

use App\Actions\UpdateCompanyCurrencies;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('sets the currencies in the order given', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    $result = new UpdateCompanyCurrencies(
        author: $michael,
        company: $company,
        currencies: ['USD', 'CAD', 'EUR'],
    )->execute();

    expect($result)->toBeInstanceOf(Company::class);
    expect($company->refresh()->currencies)->toEqual(['USD', 'CAD', 'EUR']);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyCurrenciesUpdated
            && $job->company->id === $company->id
            && $job->user->id === $michael->id
            && $job->parameters === ['currencies' => 'USD, CAD, EUR'],
    );
});

it('refuses an empty list', function () {
    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    new UpdateCompanyCurrencies(
        author: $michael,
        company: $company,
        currencies: [],
    )->execute();
})->throws(InvalidArgumentException::class);

it('refuses a currency the catalogue cannot be priced in', function () {
    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    new UpdateCompanyCurrencies(
        author: $michael,
        company: $company,
        currencies: ['CAD', 'SCHRUTEBUCK'],
    )->execute();
})->throws(InvalidArgumentException::class);

it('refuses a currency listed twice', function () {
    $company = Company::factory()->create();
    $michael = User::factory()->create(['company_id' => $company->id]);
    $this->grant($michael, PermissionEnum::CompanyManage);

    new UpdateCompanyCurrencies(
        author: $michael,
        company: $company,
        currencies: ['CAD', 'USD', 'CAD'],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author may not look after the company', function () {
    $company = Company::factory()->create();
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $this->grant($dwight, PermissionEnum::StaffView);

    new UpdateCompanyCurrencies(
        author: $dwight,
        company: $company,
        currencies: ['CAD'],
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author belongs to another company', function () {
    $company = Company::factory()->create();
    $robert = User::factory()->create();
    $this->grant($robert, PermissionEnum::CompanyManage);

    new UpdateCompanyCurrencies(
        author: $robert,
        company: $company,
        currencies: ['CAD'],
    )->execute();
})->throws(ModelNotFoundException::class);
