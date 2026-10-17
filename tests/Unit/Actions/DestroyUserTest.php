<?php

declare(strict_types=1);

use App\Actions\DestroyUser;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('destroys a user of the company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $member = User::factory()->create(['company_id' => $company->id]);

    new DestroyUser(
        author: $owner,
        user: $member,
    )->execute();

    $this->assertSoftDeleted($member);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserDeleted
            && $job->company->id === $company->id
            && $job->user->id === $owner->id
            && $job->parameters === ['email' => $member->email],
    );
});

it('throws when the author is not the owner', function () {
    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $member = User::factory()->create(['company_id' => $company->id]);
    $otherMember = User::factory()->create(['company_id' => $company->id]);

    new DestroyUser(
        author: $otherMember,
        user: $member,
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the owner deletes themselves', function () {
    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    new DestroyUser(
        author: $owner,
        user: $owner,
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the user belongs to another company', function () {
    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $stranger = User::factory()->create();

    new DestroyUser(
        author: $owner,
        user: $stranger,
    )->execute();
})->throws(ModelNotFoundException::class);
