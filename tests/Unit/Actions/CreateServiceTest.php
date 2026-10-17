<?php

declare(strict_types=1);

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
use Illuminate\Support\Facades\Queue;

it('creates a service with its steps at the end of its category', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $colours = ServiceCategory::factory()->create(['company_id' => $company->id]);
    Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $colours->id, 'position' => 1]);

    $service = new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Colour'],
        prices: ['CAD' => 3500],
        steps: [
            ['name' => ['en' => 'Application'], 'duration' => 20, 'requires_staff' => true],
            ['name' => ['en' => 'Processing'], 'duration' => 30, 'requires_staff' => false],
            ['name' => ['en' => 'Rinse'], 'duration' => 15, 'requires_staff' => true],
        ],
        description: ['en' => 'Roots and lengths'],
        category: $colours,
    )->execute();

    expect($service)->toBeInstanceOf(Service::class);
    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'company_id' => $company->id,
        'service_category_id' => $colours->id,
        'is_active' => true,
        'position' => 2,
    ]);
    expect($service->refresh()->name)->toEqual(['en' => 'Colour']);
    expect($service->prices)->toEqual(['CAD' => 3500]);
    expect($service->description)->toEqual(['en' => 'Roots and lengths']);
    $this->assertDatabaseHas('service_steps', ['service_id' => $service->id, 'position' => 1, 'duration' => 20, 'requires_staff' => true]);
    $this->assertDatabaseHas('service_steps', ['service_id' => $service->id, 'position' => 2, 'duration' => 30, 'requires_staff' => false]);
    $this->assertDatabaseHas('service_steps', ['service_id' => $service->id, 'position' => 3, 'duration' => 15, 'requires_staff' => true]);
    expect($service->steps[1]->name)->toEqual(['en' => 'Processing']);
    expect($service->duration)->toEqual(65);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCreated
            && $job->user->id === $michael->id
            && $job->parameters === ['name' => 'Colour'],
    );
});

it('creates a service without a category', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    $service = new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Beard trim'],
        prices: ['CAD' => 0],
        steps: [['duration' => 10, 'requires_staff' => true]],
    )->execute();

    expect($service->service_category_id)->toBeNull();
    expect($service->position)->toEqual(1);
});

it('throws without a step', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateService(author: $michael, company: $company, name: ['en' => 'Colour'], prices: ['CAD' => 3500], steps: [])->execute();
})->throws(InvalidArgumentException::class);

it('throws when a step lasts less than a minute', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Colour'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 0, 'requires_staff' => true], ['duration' => 30, 'requires_staff' => false]],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the price is negative', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Colour'],
        prices: ['CAD' => -1],
        steps: [['duration' => 20, 'requires_staff' => true]],
    )->execute();
})->throws(InvalidArgumentException::class);

it('stores one price per currency of the company', function () {
    Queue::fake();

    $company = Company::factory()->create(['currencies' => ['CAD', 'USD']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    $service = new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Colour'],
        prices: ['CAD' => 6500, 'USD' => 4800],
        steps: [['duration' => 20, 'requires_staff' => true]],
    )->execute();

    expect($service->refresh()->prices)->toEqual(['CAD' => 6500, 'USD' => 4800]);
});

it('creates a service without a price in some currencies', function () {
    Queue::fake();

    $company = Company::factory()->create(['currencies' => ['CAD', 'USD']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    $service = new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Colour'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 20, 'requires_staff' => true]],
    )->execute();

    expect($service->missingCurrencies())->toEqual(['USD']);
    expect($service->isBookableIn('CAD'))->toBeTrue();
    expect($service->isBookableIn('USD'))->toBeFalse();
});

it('throws when no step requires a team member', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Colour'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 20, 'requires_staff' => false]],
    )->execute();
})->throws(InvalidArgumentException::class, 'A service needs at least one step that requires a team member');

it('throws when a price is in a currency the company does not use', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Colour'],
        prices: ['CAD' => 6500, 'EUR' => 4500],
        steps: [['duration' => 20, 'requires_staff' => true]],
    )->execute();
})->throws(InvalidArgumentException::class, 'EUR is not a currency of the company');

it('throws when the company already has a service of that name', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut']]);

    new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Haircut'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 45, 'requires_staff' => true]],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the category belongs to another company', function () {
    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateService(
        author: $michael,
        company: $company,
        name: ['en' => 'Haircut'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 45, 'requires_staff' => true]],
        category: ServiceCategory::factory()->create(),
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when a manager changes the catalogue', function () {
    $company = Company::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    new CreateService(
        author: $dwight,
        company: $company,
        name: ['en' => 'Haircut'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 45, 'requires_staff' => true]],
    )->execute();
})->throws(ModelNotFoundException::class);

it('writes the name in every language of the salon', function () {
    Queue::fake();

    $company = Company::factory()->create(['languages' => ['fr', 'en']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    $result = new CreateService(author: $michael, company: $company, name: ['fr' => 'Coupe', 'en' => 'Haircut'], prices: ['CAD' => 3500], steps: [['duration' => 30, 'requires_staff' => true]], description: ['fr' => 'Courte ou longue', 'en' => null])->execute();

    expect($result->refresh()->name)->toEqual(['fr' => 'Coupe', 'en' => 'Haircut']);
    expect($result->description)->toEqual(['fr' => 'Courte ou longue']);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->parameters === ['name' => 'Coupe'],
    );
});

it('creates a service without a name and logs it as untitled', function () {
    Queue::fake();

    $company = Company::factory()->create(['languages' => ['en', 'fr']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    $service = new CreateService(author: $michael, company: $company, name: ['fr' => 'Coupe', 'en' => ''], prices: ['CAD' => 3500], steps: [['duration' => 30, 'requires_staff' => true]])->execute();

    expect($service->name)->toEqual(['fr' => 'Coupe']);
    expect($service->missingLanguages())->toEqual(['en']);
    expect($service->isBookableIn('CAD'))->toBeFalse();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->parameters === ['name' => 'Untitled'],
    );
});

it('throws when a text is written in a language the salon does not use', function () {
    $company = Company::factory()->create(['languages' => ['en']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);

    new CreateService(author: $michael, company: $company, name: ['en' => 'Haircut', 'es' => 'Corte'], prices: ['CAD' => 3500], steps: [['duration' => 30, 'requires_staff' => true]])->execute();
})->throws(InvalidArgumentException::class, 'es is not a language of the company');

it('allows the same name in two different languages', function () {
    Queue::fake();

    $company = Company::factory()->create(['languages' => ['en', 'fr']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Balayage', 'fr' => 'Mèches']]);

    $result = new CreateService(author: $michael, company: $company, name: ['en' => 'Highlights', 'fr' => 'Balayage'], prices: ['CAD' => 3500], steps: [['duration' => 30, 'requires_staff' => true]])->execute();

    expect($result->name)->toEqual(['en' => 'Highlights', 'fr' => 'Balayage']);
});

it('throws when the name is taken in the same language', function () {
    $company = Company::factory()->create(['languages' => ['en', 'fr']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut', 'fr' => 'Coupe']]);

    new CreateService(author: $michael, company: $company, name: ['en' => 'Trim', 'fr' => 'Coupe'], prices: ['CAD' => 3500], steps: [['duration' => 30, 'requires_staff' => true]])->execute();
})->throws(InvalidArgumentException::class, 'The company already has a service called Coupe');
