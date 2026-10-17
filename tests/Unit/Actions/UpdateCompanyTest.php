<?php

declare(strict_types=1);

use App\Actions\UpdateCompany;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('updates the company', function () {
    Queue::fake();

    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::CompanyManage);

    $result = new UpdateCompany(
        author: $user,
        company: $company,
        name: 'Dunder Mifflin Paper Company',
        legalName: 'Dunder Mifflin Inc.',
        websiteUrl: 'https://dundermifflin.com',
        billingEmail: 'accounting@dundermifflin.com',
    )->execute();

    expect($result)->toBeInstanceOf(Company::class);

    $company->refresh();

    expect($company->name)->toEqual('Dunder Mifflin Paper Company');
    expect($company->legal_name)->toEqual('Dunder Mifflin Inc.');
    expect($company->billing_email)->toEqual('accounting@dundermifflin.com');

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyUpdated
            && $job->company->id === $company->id
            && $job->user->id === $user->id
            && $job->parameters === ['name' => 'Dunder Mifflin Paper Company'],
    );
});

it('does not change the slug', function () {
    Queue::fake();

    $company = Company::factory()->create(['slug' => 'dunder-mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::CompanyManage);

    new UpdateCompany(
        author: $user,
        company: $company,
        name: 'Michael Scott Paper Company',
    )->execute();

    expect($company->refresh()->slug)->toEqual('dunder-mifflin');
});

it('throws when the author belongs to another company', function () {
    $company = Company::factory()->create();
    $stranger = User::factory()->create();
    $this->grant($stranger, PermissionEnum::CompanyManage);

    new UpdateCompany(
        author: $stranger,
        company: $company,
        name: 'Michael Scott Paper Company',
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author may not look after the company', function () {
    $company = Company::factory()->create();
    $member = User::factory()->create(['company_id' => $company->id]);
    $this->grant($member, PermissionEnum::StaffView);

    new UpdateCompany(
        author: $member,
        company: $company,
        name: 'Michael Scott Paper Company',
    )->execute();
})->throws(ModelNotFoundException::class);
