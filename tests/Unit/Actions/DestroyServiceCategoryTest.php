<?php

declare(strict_types=1);

use App\Actions\DestroyServiceCategory;
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

it('deletes a category and keeps its services without a category', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Colours'], 'position' => 1]);
    $care = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $shave = Service::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $colour = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $colours->id, 'position' => 1]);

    new DestroyServiceCategory(author: $michael, category: $colours)->execute();

    $this->assertModelMissing($colours);
    $this->assertDatabaseHas('services', ['id' => $colour->id, 'service_category_id' => null, 'position' => 2]);
    expect($shave->refresh()->position)->toEqual(1);
    expect($care->refresh()->position)->toEqual(1);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryDeleted
            && $job->parameters === ['name' => 'Colours'],
    );
});

it('throws when a manager changes the catalogue', function () {
    $category = ServiceCategory::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $category->company_id]), Role::MANAGER);

    new DestroyServiceCategory(author: $dwight, category: $category)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the category belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new DestroyServiceCategory(author: $michael, category: ServiceCategory::factory()->create())->execute();
})->throws(ModelNotFoundException::class);

it('deletes a category with its services when asked to', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $barbering = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $shave = Service::factory()->create(['company_id' => $company->id, 'service_category_id' => $barbering->id]);

    new DestroyServiceCategory(author: $michael, category: $barbering, withServices: true)->execute();

    $this->assertModelMissing($barbering);
    $this->assertModelMissing($shave);
    $this->assertDatabaseMissing('service_steps', ['service_id' => $shave->id]);
});
