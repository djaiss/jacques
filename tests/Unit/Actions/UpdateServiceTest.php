<?php

declare(strict_types=1);

use App\Actions\UpdateService;
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

it('updates a service and replaces its steps', function () {
    Queue::fake();

    $service = Service::factory()->create(['name' => ['en' => 'Colour'], 'prices' => ['CAD' => 3500]]);
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    $service = new UpdateService(
        author: $michael,
        service: $service,
        name: ['en' => 'Full colour'],
        prices: ['CAD' => 4200],
        steps: [
            ['duration' => 25, 'requires_staff' => true], ['duration' => 35, 'requires_staff' => false],
            ['duration' => 10, 'requires_staff' => true],
        ],
    )->execute();

    $service->refresh();

    expect($service->name)->toEqual(['en' => 'Full colour']);
    expect($service->prices)->toEqual(['CAD' => 4200]);
    expect($service->description)->toBeNull();
    expect($service->steps)->toHaveCount(3);
    expect($service->duration)->toEqual(70);
    expect($service->free_duration)->toEqual(35);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceUpdated
            && $job->parameters === ['name' => 'Full colour'],
    );
});

it('moves a service to the end of its new category and closes the gap', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $cuts = ServiceCategory::factory()->create(['company_id' => $company->id]);
    $colours = ServiceCategory::factory()->create(['company_id' => $company->id]);
    $fringe = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 1]);
    $haircut = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $cuts->id, 'position' => 2]);
    Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $colours->id, 'position' => 1]);

    new UpdateService(
        author: $michael,
        service: $fringe,
        name: $fringe->name,
        prices: ['CAD' => 1500],
        steps: [['duration' => 15, 'requires_staff' => true]],
        category: $colours,
    )->execute();

    $this->assertDatabaseHas('services', ['id' => $fringe->id, 'service_category_id' => $colours->id, 'position' => 2]);
    expect($haircut->refresh()->position)->toEqual(1);
});

it('throws without a step', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateService(author: $michael, service: $service, name: ['en' => 'Colour'], prices: ['CAD' => 3500], steps: [])->execute();
})->throws(InvalidArgumentException::class);

it('throws when another service has that name', function () {
    $service = Service::factory()->create();
    Service::factory()->create(['company_id' => $service->company_id, 'name' => ['en' => 'Haircut']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateService(
        author: $michael,
        service: $service,
        name: ['en' => 'Haircut'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 45, 'requires_staff' => true]],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the category belongs to another company', function () {
    $service = Service::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $service->company_id]), PermissionEnum::ServiceManage);

    new UpdateService(
        author: $michael,
        service: $service,
        name: ['en' => 'Haircut'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 45, 'requires_staff' => true]],
        category: ServiceCategory::factory()->create(),
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when a manager changes the catalogue', function () {
    $service = Service::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $service->company_id]), Role::MANAGER);

    new UpdateService(
        author: $dwight,
        service: $service,
        name: ['en' => 'Haircut'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 45, 'requires_staff' => true]],
    )->execute();
})->throws(ModelNotFoundException::class);

it('keeps the texts of a language the salon stopped using', function () {
    Queue::fake();

    $company = Company::factory()->create(['languages' => ['en', 'es']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $service = Service::factory()->create([
        'company_id' => $company->id,
        'name' => ['en' => 'Haircut', 'es' => 'Corte'],
        'description' => ['en' => 'Short or long', 'es' => 'Corto o largo'],
    ]);
    $company->update(['languages' => ['en']]);

    new UpdateService(
        author: $michael,
        service: $service->refresh(),
        name: ['en' => 'Women\'s haircut'],
        prices: ['CAD' => 3500],
        steps: [['duration' => 45, 'requires_staff' => true]],
    )->execute();

    $company->update(['languages' => ['en', 'es']]);
    $service->refresh();

    expect($service->name)->toEqual(['es' => 'Corte', 'en' => 'Women\'s haircut']);
    expect($service->description)->toEqual(['es' => 'Corto o largo']);
    expect($service->missingLanguages())->toEqual(['en']);
});

it('shows a language added to the salon as missing until it is written', function () {
    $company = Company::factory()->create(['languages' => ['en']]);
    $service = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut']]);

    $company->update(['languages' => ['en', 'fr']]);

    expect($service->refresh()->name)->toEqual(['en' => 'Haircut']);
    expect($service->missingLanguages())->toEqual(['fr']);
});

it('keeps the prices of a currency the salon stopped using', function () {
    Queue::fake();

    $company = Company::factory()->create(['currencies' => ['CAD', 'USD']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $service = Service::factory()->create([
        'company_id' => $company->id,
        'name' => ['en' => 'Haircut'],
        'prices' => ['CAD' => 3500, 'USD' => 2600],
    ]);
    $company->update(['currencies' => ['CAD']]);

    new UpdateService(
        author: $michael,
        service: $service->refresh(),
        name: ['en' => 'Haircut'],
        prices: ['CAD' => 4000],
        steps: [['duration' => 30, 'requires_staff' => true]],
    )->execute();

    expect($service->refresh()->prices)->toEqual(['USD' => 2600, 'CAD' => 4000]);
    expect($service->missingCurrencies())->toEqual([]);
});

it('shows a currency added to the salon as missing until it is priced', function () {
    $company = Company::factory()->create(['currencies' => ['CAD']]);
    $service = Service::factory()->create(['company_id' => $company->id, 'prices' => ['CAD' => 3500]]);

    $company->update(['currencies' => ['CAD', 'USD']]);

    expect($service->refresh()->prices)->toEqual(['CAD' => 3500]);
    expect($service->missingCurrencies())->toEqual(['USD']);
});
