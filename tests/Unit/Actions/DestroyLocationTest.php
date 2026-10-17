<?php

declare(strict_types=1);

use App\Actions\DestroyLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('destroys an location', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

    new DestroyLocation(author: $author, location: $location)->execute();

    $this->assertModelMissing($location);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationDeleted
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === ['name' => 'Scranton branch'],
    );
});

it('throws when the author may not change the company', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $location = Location::factory()->create(['company_id' => $company->id]);

    new DestroyLocation(author: $author, location: $location)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the location belongs to another company', function () {
    $author = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);
    $location = Location::factory()->create();

    new DestroyLocation(author: $author, location: $location)->execute();
})->throws(ModelNotFoundException::class);
