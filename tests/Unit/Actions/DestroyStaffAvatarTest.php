<?php

declare(strict_types=1);

use App\Actions\DestroyStaffAvatar;
use App\Actions\UpdateStaffAvatar;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('removes the avatar and its files', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'first_name' => 'Kevin',
        'last_name' => 'Malone',
    ]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    $withAvatar = new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('chili.jpg', 400, 400),
    )->execute();

    $path = (string) $withAvatar->avatar_path;

    $result = new DestroyStaffAvatar(
        author: $user->fresh(),
        staff: $staff->fresh(),
    )->execute();

    expect($result)->toBeInstanceOf(Staff::class);
    expect($result->avatar_path)->toBeNull();
    expect($result->hasAvatar())->toBeFalse();

    foreach (Staff::avatarPixelSizes() as $pixels) {
        Storage::disk('local')->assertMissing($path.'_'.$pixels.'.webp');
    }

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'avatar_path' => null,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffAvatarDeleted,
    );
});

it('does nothing when there is no avatar', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'avatar_path' => null,
    ]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    $result = new DestroyStaffAvatar(author: $user, staff: $staff)->execute();

    expect($result->avatar_path)->toBeNull();

    Queue::assertNotPushed(LogUserAction::class);
});

it('throws when the author may only change their own avatar', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $angela = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $author->id]);
    $oscar = Staff::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    new DestroyStaffAvatar(author: $author, staff: $oscar)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
    $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    new DestroyStaffAvatar(author: $author, staff: $stranger)->execute();
})->throws(ModelNotFoundException::class);
