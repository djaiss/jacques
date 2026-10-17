<?php

declare(strict_types=1);

use App\Actions\RestoreLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('reopens an location', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->archived()->create(['company_id' => $company->id, 'name' => 'Nashua branch']);

    $restored = new RestoreLocation(author: $author, location: $location)->execute();

    expect($restored)->toBeInstanceOf(Location::class);

    $location->refresh();

    expect($location->isArchived())->toBeFalse();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationRestored
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === ['name' => 'Nashua branch'],
    );
});

it('does not give the headquarters badge back', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->archived()->create(['company_id' => $company->id]);

    new RestoreLocation(author: $author, location: $location)->execute();

    $location->refresh();

    expect($location->is_headquarters)->toBeFalse();
});

it('throws when the author may not change the company', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $location = Location::factory()->archived()->create(['company_id' => $company->id]);

    new RestoreLocation(author: $author, location: $location)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the location belongs to another company', function () {
    $author = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);
    $location = Location::factory()->archived()->create();

    new RestoreLocation(author: $author, location: $location)->execute();
})->throws(ModelNotFoundException::class);
