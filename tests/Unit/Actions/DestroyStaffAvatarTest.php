<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyStaffAvatarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_the_avatar_and_its_files(): void
    {
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

        $this->assertInstanceOf(Staff::class, $result);
        $this->assertNull($result->avatar_path);
        $this->assertFalse($result->hasAvatar());

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
    }

    #[Test]
    public function it_does_nothing_when_there_is_no_avatar(): void
    {
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

        $this->assertNull($result->avatar_path);

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_throws_when_the_author_may_only_change_their_own_avatar(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $angela = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $author->id]);
        $oscar = Staff::factory()->create(['company_id' => $company->id]);
        $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Self);

        new DestroyStaffAvatar(author: $author, staff: $oscar)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
        $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

        new DestroyStaffAvatar(author: $author, staff: $stranger)->execute();
    }
}
