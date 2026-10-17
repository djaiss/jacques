<?php

declare(strict_types=1);

use App\Actions\UpdateStaffInformation;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('updates the information of the staff member signed in', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'first_name' => 'Dwight',
        'last_name' => 'Schrute',
    ]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    $result = new UpdateStaffInformation(
        author: $user,
        staff: $staff,
        firstName: 'Dwight',
        lastName: 'Schrute',
        displayName: 'Assistant to the Regional Manager',
        email: 'Dwight.Schrute@DunderMifflin.com',
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();

    expect($result)->toBeInstanceOf(Staff::class);

    $staff->refresh();

    expect($staff->first_name)->toEqual('Dwight');
    expect($staff->last_name)->toEqual('Schrute');
    expect($staff->display_name)->toEqual('Assistant to the Regional Manager');
    expect($staff->email)->toEqual('dwight.schrute@dundermifflin.com');

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInformationUpdated
            && $job->company->id === $company->id
            && $job->user->id === $user->id,
    );
});

it('saves the phone the job title the bio and whether clients can book', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    new UpdateStaffInformation(
        author: $user,
        staff: $staff,
        firstName: 'Stanley',
        lastName: 'Hudson',
        displayName: null,
        email: null,
        phone: '+1 570 555 0187',
        jobTitle: 'Salesman',
        bio: 'Did I stutter?',
        isBookable: false,
    )->execute();

    $staff->refresh();

    expect($staff->phone)->toEqual('+1 570 555 0187');
    expect($staff->job_title)->toEqual('Salesman');
    expect($staff->bio)->toEqual('Did I stutter?');
    expect($staff->is_bookable)->toBeFalse();
});

it('updates a colleague at company scope', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $jim = Staff::factory()->create(['company_id' => $company->id]);
    $author = User::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    new UpdateStaffInformation(
        author: $author,
        staff: $jim,
        firstName: 'Jim',
        lastName: 'Halpert',
        displayName: null,
        email: null,
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();

    expect($jim->refresh()->first_name)->toEqual('Jim');
});

it('clears the optional fields when they are null', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'display_name' => 'Big Tuna',
        'email' => 'jim.halpert@dundermifflin.com',
        'phone' => '+1 570 555 0100',
        'job_title' => 'Salesman',
        'bio' => 'Pranks Dwight.',
    ]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    new UpdateStaffInformation(
        author: $user,
        staff: $staff,
        firstName: 'Jim',
        lastName: null,
        displayName: null,
        email: null,
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();

    $staff->refresh();

    expect($staff->last_name)->toBeNull();
    expect($staff->display_name)->toBeNull();
    expect($staff->email)->toBeNull();
    expect($staff->phone)->toBeNull();
    expect($staff->job_title)->toBeNull();
    expect($staff->bio)->toBeNull();
});

it('throws when the author may only edit themselves', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $author->id, 'first_name' => 'Angela']);
    $oscar = Staff::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    new UpdateStaffInformation(
        author: $author,
        staff: $oscar,
        firstName: 'Oscar',
        lastName: 'Martinez',
        displayName: null,
        email: null,
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author holds no role', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $author->id]);

    new UpdateStaffInformation(
        author: $author,
        staff: $staff,
        firstName: 'Creed',
        lastName: 'Bratton',
        displayName: null,
        email: null,
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
    $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    new UpdateStaffInformation(
        author: $author,
        staff: $stranger,
        firstName: 'Creed',
        lastName: 'Bratton',
        displayName: null,
        email: null,
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when another staff member of the company uses the email', function () {
    $company = Company::factory()->create();
    Staff::factory()->create(['company_id' => $company->id, 'email' => 'dwight.schrute@dundermifflin.com']);
    $mose = Staff::factory()->create(['company_id' => $company->id, 'email' => null]);
    $author = User::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    new UpdateStaffInformation(
        author: $author,
        staff: $mose,
        firstName: 'Mose',
        lastName: 'Schrute',
        displayName: null,
        email: 'Dwight.Schrute@DunderMifflin.com',
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();
})->throws(InvalidArgumentException::class);

it('keeps the email the staff member already uses', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id, 'email' => 'dwight.schrute@dundermifflin.com']);
    $author = User::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    new UpdateStaffInformation(
        author: $author,
        staff: $dwight,
        firstName: 'Dwight',
        lastName: 'Schrute',
        displayName: null,
        email: 'dwight.schrute@dundermifflin.com',
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();

    expect($dwight->refresh()->email)->toEqual('dwight.schrute@dundermifflin.com');
});

it('deletes the invitation when the email is removed', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id, 'email' => 'dwight.schrute@dundermifflin.com']);
    $invitation = StaffInvitation::factory()->create(['staff_id' => $dwight->id]);
    $author = User::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    new UpdateStaffInformation(
        author: $author,
        staff: $dwight,
        firstName: 'Dwight',
        lastName: 'Schrute',
        displayName: null,
        email: null,
        phone: null,
        jobTitle: null,
        bio: null,
        isBookable: true,
    )->execute();

    $this->assertModelMissing($invitation);
});
