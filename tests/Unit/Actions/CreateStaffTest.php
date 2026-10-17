<?php

declare(strict_types=1);

use App\Actions\CreateStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('creates a staff member', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::StaffCreate);

    $staff = new CreateStaff(
        author: $user,
        company: $company,
        firstName: 'Michael',
        lastName: 'Scott',
        displayName: 'The World Best Boss',
        email: 'Michael.Scott@DunderMifflin.com',
    )->execute();

    expect($staff)->toBeInstanceOf(Staff::class);
    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'company_id' => $company->id,
        'user_id' => null,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'display_name' => 'The World Best Boss',
        'email' => 'michael.scott@dundermifflin.com',
        'is_bookable' => true,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffCreated
            && $job->company->id === $company->id
            && $job->user->id === $user->id
            && $job->parameters === ['name' => 'The World Best Boss'],
    );
});

it('saves every optional field', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::StaffCreate);

    $staff = new CreateStaff(
        author: $user,
        company: $company,
        firstName: 'Pam',
        lastName: 'Beesly',
        phone: '+1 570 555 0142',
        jobTitle: 'Receptionist',
        bio: 'Draws the watercolours in the lobby.',
        isBookable: false,
    )->execute();

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'phone' => '+1 570 555 0142',
        'job_title' => 'Receptionist',
        'bio' => 'Draws the watercolours in the lobby.',
        'is_bookable' => false,
    ]);
});

it('accepts a staff member without a last name', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::StaffCreate);

    $staff = new CreateStaff(
        author: $user,
        company: $company,
        firstName: 'Creed',
    )->execute();

    expect($staff->refresh()->last_name)->toBeNull();
    expect($staff->full_name)->toEqual('Creed');
});

it('places the staff member after the last one of the same company', function () {
    Queue::fake();

    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    Staff::factory()->create(['company_id' => $dunderMifflin->id, 'position' => 1]);
    Staff::factory()->create(['company_id' => $dunderMifflin->id, 'position' => 4]);
    Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id, 'position' => 9]);
    $user = User::factory()->create(['company_id' => $dunderMifflin->id]);
    $this->grant($user, PermissionEnum::StaffCreate);

    $staff = new CreateStaff(
        author: $user,
        company: $dunderMifflin,
        firstName: 'Kevin',
        lastName: 'Malone',
    )->execute();

    expect($staff->refresh()->position)->toEqual(5);
});

it('links an account of the same company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffCreate);
    $account = User::factory()->create(['company_id' => $company->id]);

    $staff = new CreateStaff(
        author: $author,
        company: $company,
        firstName: 'Andy',
        lastName: 'Bernard',
        user: $account,
    )->execute();

    expect($staff->refresh()->user_id)->toEqual($account->id);
    expect($account->refresh()->staff->id)->toEqual($staff->id);
});

it('throws when the account belongs to another company', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffCreate);
    $stranger = User::factory()->create();

    new CreateStaff(
        author: $author,
        company: $company,
        firstName: 'Todd',
        lastName: 'Packer',
        user: $stranger,
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the account already gives access to a staff member', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffCreate);
    $account = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $account->id]);

    new CreateStaff(
        author: $author,
        company: $company,
        firstName: 'Ryan',
        lastName: 'Howard',
        user: $account,
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author belongs to another company', function () {
    $company = Company::factory()->create();
    $stranger = User::factory()->create();
    $this->grant($stranger, PermissionEnum::StaffCreate);

    new CreateStaff(
        author: $stranger,
        company: $company,
        firstName: 'Jim',
        lastName: 'Halpert',
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author may not add anybody', function () {
    $company = Company::factory()->create();
    $member = User::factory()->create(['company_id' => $company->id]);
    $this->grant($member, PermissionEnum::StaffView);

    new CreateStaff(
        author: $member,
        company: $company,
        firstName: 'Jim',
        lastName: 'Halpert',
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when another staff member of the company uses the email', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::StaffCreate);
    Staff::factory()->create(['company_id' => $company->id, 'email' => 'dwight.schrute@dundermifflin.com']);

    new CreateStaff(
        author: $user,
        company: $company,
        firstName: 'Mose',
        email: 'Dwight.Schrute@DunderMifflin.com',
    )->execute();
})->throws(InvalidArgumentException::class);

it('accepts an email used in another company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->grant($user, PermissionEnum::StaffCreate);
    Staff::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);

    $staff = new CreateStaff(
        author: $user,
        company: $company,
        firstName: 'Dwight',
        email: 'dwight.schrute@dundermifflin.com',
    )->execute();

    expect($staff->email)->toEqual('dwight.schrute@dundermifflin.com');
});
