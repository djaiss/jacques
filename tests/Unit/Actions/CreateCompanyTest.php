<?php

declare(strict_types=1);

use App\Actions\CreateCompany;
use App\Enums\OnboardingStepEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\CreateDefaultServices;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

it('queues the creation of the default catalogue', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    Queue::assertPushedOn(
        queue: 'high',
        job: CreateDefaultServices::class,
        callback: fn (CreateDefaultServices $job): bool => $job->companyId === $company->id,
    );
});

it('creates a company and its owner', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    expect($company)->toBeInstanceOf(Company::class);
    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'name' => 'Dunder Mifflin',
        'slug' => 'dunder-mifflin',
    ]);
    $this->assertDatabaseHas('users', [
        'company_id' => $company->id,
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    $owner = $company->users()->firstOrFail();

    expect(Hash::check('thatswhatshesaid', $owner->password_hash))->toBeTrue();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyCreated
            && $job->company->id === $company->id
            && $job->user->id === $owner->id,
    );
});

it('creates the staff record of the owner linked to their account', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    $owner = $company->users()->firstOrFail();

    $this->assertDatabaseHas('staff', [
        'company_id' => $company->id,
        'user_id' => $owner->id,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'email' => 'michael.scott@dundermifflin.com',
    ]);
    expect($owner->staff->name)->toEqual('Michael Scott');
});

it('gives the company the roles it starts with', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    expect($company->roles()->pluck('slug')->sort()->values()->all())->toEqual([Role::MANAGER, Role::OWNER, Role::RECEPTIONIST, Role::STAFF]);
});

it('gives the first user the owner role', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    $owner = $company->users()->firstOrFail();

    expect($owner->roles->pluck('slug')->all())->toEqual([Role::OWNER]);
    expect($owner->permission(PermissionEnum::BillingManage)->forCompany($company)->allowed())->toBeTrue();
});

it('starts the company on a trial', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    expect($company->isOnTrial())->toBeTrue();
});

it('owes the new company the welcome screen', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    expect($company->refresh()->is_welcome_screen_pending)->toBeTrue();
});

it('starts the new company on the first onboarding step', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    $company->refresh();

    expect($company->is_onboarding_pending)->toBeTrue();
    expect($company->onboarding_step)->toEqual(OnboardingStepEnum::Location);
});

it('writes the catalogue in english until the salon chooses its languages', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    expect($company->refresh()->languages)->toEqual(['en']);
});

it('prices the catalogue in canadian dollars until the salon chooses its currencies', function () {
    Queue::fake();

    $company = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    expect($company->refresh()->currencies)->toEqual(['CAD']);
});

it('makes the slug unique', function () {
    Queue::fake();

    $first = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Michael',
        lastName: 'Scott',
        email: 'michael.scott@dundermifflin.com',
        password: 'thatswhatshesaid',
    )->execute();

    $second = new CreateCompany(
        name: 'Dunder Mifflin',
        firstName: 'Jim',
        lastName: 'Halpert',
        email: 'jim.halpert@dundermifflin.com',
        password: 'bearsbeatsbattlestar',
    )->execute();

    expect($first->slug)->toEqual('dunder-mifflin');
    expect($second->slug)->toEqual('dunder-mifflin-2');
});
