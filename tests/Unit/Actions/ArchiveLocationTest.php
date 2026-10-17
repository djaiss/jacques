<?php

declare(strict_types=1);

use App\Actions\ArchiveLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('archives an location', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);

    $archived = new ArchiveLocation(author: $author, location: $location)->execute();

    expect($archived)->toBeInstanceOf(Location::class);

    $location->refresh();

    expect($location->isArchived())->toBeTrue();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationArchived
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === ['name' => 'Utica branch'],
    );
});

it('takes the headquarters badge away', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->primary()->create(['company_id' => $company->id]);

    new ArchiveLocation(author: $author, location: $location)->execute();

    $location->refresh();

    expect($location->is_headquarters)->toBeFalse();
});

it('throws when the author may not change the company', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $location = Location::factory()->create(['company_id' => $company->id]);

    new ArchiveLocation(author: $author, location: $location)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the location belongs to another company', function () {
    $author = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);
    $location = Location::factory()->create();

    new ArchiveLocation(author: $author, location: $location)->execute();
})->throws(ModelNotFoundException::class);

it('lets a manager archive only the locations they are assigned to', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $stamford = Location::factory()->create(['company_id' => $company->id]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $michael->id])->locations()->attach($scranton);

    new ArchiveLocation(author: $michael, location: $scranton)->execute();

    expect($scranton->refresh()->isArchived())->toBeTrue();

    new ArchiveLocation(author: $michael, location: $stamford)->execute();
})->throws(ModelNotFoundException::class);
