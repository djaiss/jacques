<?php

declare(strict_types=1);

use App\Actions\UpdateServiceCategory;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('updates a service category', function () {
    Queue::fake();

    $category = ServiceCategory::factory()->create(['name' => ['en' => 'Cuts']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

    $category = new UpdateServiceCategory(author: $michael, category: $category, name: ['en' => 'Haircuts'])->execute();

    $category->refresh();

    expect($category->name)->toEqual(['en' => 'Haircuts']);
    expect($category->description)->toBeNull();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryUpdated
            && $job->parameters === ['name' => 'Haircuts'],
    );
});

it('keeps its own name', function () {
    Queue::fake();

    $category = ServiceCategory::factory()->create(['name' => ['en' => 'Cuts']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

    new UpdateServiceCategory(author: $michael, category: $category, name: ['en' => 'Cuts'], description: ['en' => 'Short and long'])->execute();

    expect($category->refresh()->description)->toEqual(['en' => 'Short and long']);
});

it('throws when another category has that name', function () {
    $category = ServiceCategory::factory()->create(['name' => ['en' => 'Cuts']]);
    ServiceCategory::factory()->create(['company_id' => $category->company_id, 'name' => ['en' => 'Colours']]);
    $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

    new UpdateServiceCategory(author: $michael, category: $category, name: ['en' => 'Colours'])->execute();
})->throws(InvalidArgumentException::class);

it('throws when a manager changes the catalogue', function () {
    $category = ServiceCategory::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $category->company_id]), Role::MANAGER);

    new UpdateServiceCategory(author: $dwight, category: $category, name: ['en' => 'Cuts'])->execute();
})->throws(ModelNotFoundException::class);

it('throws when the category belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

    new UpdateServiceCategory(author: $michael, category: ServiceCategory::factory()->create(), name: ['en' => 'Cuts'])->execute();
})->throws(ModelNotFoundException::class);
