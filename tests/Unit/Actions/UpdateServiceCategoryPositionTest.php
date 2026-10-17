<?php

declare(strict_types=1);

use App\Actions\UpdateServiceCategoryPosition;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('moves a category up and shifts the ones it passes', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $cuts = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $care = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 3]);

    new UpdateServiceCategoryPosition(author: $michael, category: $care, position: 1)->execute();

    expect($company->serviceCategories()->pluck('id')->all())->toEqual([$care->id, $cuts->id, $colours->id]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ServiceCategoryMoved,
    );
});

it('moves a category down and shifts the ones it passes', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
    $cuts = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $colours = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $care = ServiceCategory::factory()->create(['company_id' => $company->id, 'position' => 3]);

    new UpdateServiceCategoryPosition(author: $michael, category: $cuts, position: 3)->execute();

    expect($company->serviceCategories()->pluck('id')->all())->toEqual([$colours->id, $care->id, $cuts->id]);
    expect($company->serviceCategories()->pluck('position')->all())->toEqual([1, 2, 3]);
});

it('throws when the position is out of range', function () {
    $category = ServiceCategory::factory()->create(['position' => 1]);
    $michael = $this->grant(User::factory()->create(['company_id' => $category->company_id]), PermissionEnum::ServiceManage);

    new UpdateServiceCategoryPosition(author: $michael, category: $category, position: 2)->execute();
})->throws(InvalidArgumentException::class);

it('throws when a manager changes the catalogue', function () {
    $category = ServiceCategory::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $category->company_id]), Role::MANAGER);

    new UpdateServiceCategoryPosition(author: $dwight, category: $category, position: 1)->execute();
})->throws(ModelNotFoundException::class);
