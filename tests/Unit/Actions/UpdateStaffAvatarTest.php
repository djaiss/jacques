<?php

declare(strict_types=1);

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

it('stores the avatar and logs the action', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'first_name' => 'Dwight',
        'last_name' => 'Schrute',
    ]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    $result = new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('dwight.jpg', 400, 400),
    )->execute();

    expect($result)->toBeInstanceOf(Staff::class);
    expect((string) $result->avatar_path)->toStartWith('avatars/'.$staff->id.'/');

    expect((string) $result->avatar_path)->not->toContain('dwight');
    expect((string) $result->avatar_path)->not->toContain('.webp');

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'avatar_path' => $result->avatar_path,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffAvatarUpdated
            && $job->company->id === $company->id
            && $job->user->id === $user->id,
    );
});

it('writes a square version at one and two times the displayed size', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    $result = new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('michael.jpg', 800, 200),
    )->execute();

    foreach (Staff::avatarPixelSizes() as $pixels) {
        $path = $result->avatarVariantPath($pixels);

        Storage::disk('local')->assertExists($path);

        $size = getimagesizefromstring((string) Storage::disk('local')->get($path));

        expect($size[0])->toEqual($pixels);
        expect($size[1])->toEqual($pixels);
        expect($size['mime'])->toEqual('image/webp');
    }

    expect(Staff::avatarPixelSizes())->toEqual([96, 192]);
});

it('removes the files of the previous avatar', function () {
    Queue::fake();
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    $first = new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('pam.jpg', 400, 400),
    )->execute();

    $firstPath = (string) $first->avatar_path;

    $second = new UpdateStaffAvatar(
        author: $user->fresh(),
        staff: $staff->fresh(),
        file: UploadedFile::fake()->image('jim.jpg', 400, 400),
    )->execute();

    expect($second->avatar_path)->not->toEqual($firstPath);

    foreach (Staff::avatarPixelSizes() as $pixels) {
        Storage::disk('local')->assertMissing($firstPath.'_'.$pixels.'.webp');
        Storage::disk('local')->assertExists($second->avatarVariantPath($pixels));
    }
});

it('refuses a file that is not an image we accept', function () {
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->create('beets.pdf', 10, 'application/pdf'),
    )->execute();
})->throws(InvalidArgumentException::class);

it('refuses a file larger than five megabytes', function () {
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    new UpdateStaffAvatar(
        author: $user,
        staff: $staff,
        file: UploadedFile::fake()->image('creed.jpg')->size(6 * 1024),
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author may only change their own avatar', function () {
    Storage::fake('local');

    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $angela = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $author->id]);
    $oscar = Staff::factory()->create(['company_id' => $company->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Self);

    new UpdateStaffAvatar(
        author: $author,
        staff: $oscar,
        file: UploadedFile::fake()->image('mose.jpg', 400, 400),
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    Storage::fake('local');

    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
    $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
    $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

    new UpdateStaffAvatar(
        author: $author,
        staff: $stranger,
        file: UploadedFile::fake()->image('mose.jpg', 400, 400),
    )->execute();
})->throws(ModelNotFoundException::class);
