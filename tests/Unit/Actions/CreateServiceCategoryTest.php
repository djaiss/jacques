<?php

declare(strict_types=1);

use App\Actions\CreateServiceCategory;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('creates a service category after the existing ones', function () {
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

    expect($category)->toBeInstanceOf(ServiceCategory::class);
    $category->refresh();

    expect($category->company_id)->toEqual($company->id);
    expect($category->name)->toEqual(['en' => 'Colours']);
    expect($category->description)->toEqual(['en' => 'Everything that changes the colour']);
    expect($category->position)->toEqual(2);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryCreated
            && $job->user->id === $michael->id
            && $job->parameters === ['name' => 'Colours'],
    );
});

it('creates an unnamed category', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    $category = new CreateServiceCategory(author: $michael, company: $company, name: ['en' => '  '])->execute();

    expect($category->refresh()->name)->toEqual([]);
    expect($category->missingLanguages())->toEqual(['en']);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->parameters === ['name' => 'Untitled'],
    );
});

it('throws when the company already has a category of that name', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Cuts']]);

    new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Cuts'])->execute();
})->throws(InvalidArgumentException::class);

it('throws when a manager changes the catalogue', function () {
    $company = Company::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    new CreateServiceCategory(author: $dwight, company: $company, name: ['en' => 'Cuts'])->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new CreateServiceCategory(author: $michael, company: Company::factory()->create(), name: ['en' => 'Cuts'])->execute();
})->throws(ModelNotFoundException::class);

it('writes the name in every language of the salon', function () {
    Queue::fake();

    $company = Company::factory()->create(['languages' => ['fr', 'en']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    $result = new CreateServiceCategory(author: $michael, company: $company, name: ['fr' => 'Coupe', 'en' => 'Haircut'], description: ['fr' => 'Courte ou longue', 'en' => null])->execute();

    expect($result->refresh()->name)->toEqual(['fr' => 'Coupe', 'en' => 'Haircut']);
    expect($result->description)->toEqual(['fr' => 'Courte ou longue']);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->parameters === ['name' => 'Coupe'],
    );
});

it('throws when a text is written in a language the salon does not use', function () {
    $company = Company::factory()->create(['languages' => ['en']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Haircut', 'es' => 'Corte'])->execute();
})->throws(InvalidArgumentException::class, 'es is not a language of the company');

it('allows the same name in two different languages', function () {
    Queue::fake();

    $company = Company::factory()->create(['languages' => ['en', 'fr']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Balayage', 'fr' => 'Mèches']]);

    $result = new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Highlights', 'fr' => 'Balayage'])->execute();

    expect($result->name)->toEqual(['en' => 'Highlights', 'fr' => 'Balayage']);
});

it('throws when the name is taken in the same language', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut', 'fr' => 'Coupe']]);

    new CreateServiceCategory(author: $michael, company: $company, name: ['en' => 'Trim', 'fr' => 'Coupe'])->execute();
})->throws(InvalidArgumentException::class, 'The company already has a service category called Coupe');
