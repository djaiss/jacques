<?php

declare(strict_types=1);

use App\Actions\UpdateUserInformation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('updates the user themselves', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'jim.halpert@dundermifflin.com',
    ]);

    $result = new UpdateUserInformation(
        author: $user,
        user: $user,
        email: 'jim.halpert@athlead.com',
        locale: 'fr_FR',
    )->execute();

    expect($result)->toBeInstanceOf(User::class);

    $user->refresh();

    expect($user->email)->toEqual('jim.halpert@athlead.com');
    expect($user->locale)->toEqual('fr_FR');

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserInformationUpdated
            && $job->company->id === $company->id
            && $job->user->id === $user->id,
    );
});

it('lets the owner update another user', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $member = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'kevin.malone@dundermifflin.com',
    ]);

    new UpdateUserInformation(
        author: $owner,
        user: $member,
        email: 'kevin.malone@dundermifflin.co.uk',
    )->execute();

    expect($member->refresh()->email)->toEqual('kevin.malone@dundermifflin.co.uk');
});

it('resets the email verification when the email changes', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'pam.beesly@dundermifflin.com',
        'email_verified_at' => now(),
    ]);

    new UpdateUserInformation(
        author: $user,
        user: $user,
        email: 'pam.halpert@dundermifflin.com',
    )->execute();

    expect($user->refresh()->email_verified_at)->toBeNull();
});

it('keeps the email verification when the email does not change', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'pam.beesly@dundermifflin.com',
        'email_verified_at' => now(),
    ]);

    new UpdateUserInformation(
        author: $user,
        user: $user,
        email: 'pam.beesly@dundermifflin.com',
    )->execute();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('throws when another member updates a user', function () {
    $company = Company::factory()->create();
    $owner = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $member = User::factory()->create(['company_id' => $company->id]);
    $otherMember = User::factory()->create(['company_id' => $company->id]);

    new UpdateUserInformation(
        author: $otherMember,
        user: $member,
        email: 'toby.flenderson@dundermifflin.com',
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author belongs to another company', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    new UpdateUserInformation(
        author: $stranger,
        user: $user,
        email: 'creed.bratton@dundermifflin.com',
    )->execute();
})->throws(ModelNotFoundException::class);
