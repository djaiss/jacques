<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

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
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateStaffAvatarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_the_avatar_and_logs_the_action(): void
    {
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

        $this->assertInstanceOf(Staff::class, $result);
        $this->assertStringStartsWith('avatars/'.$staff->id.'/', (string) $result->avatar_path);

        $this->assertStringNotContainsString('dwight', (string) $result->avatar_path);
        $this->assertStringNotContainsString('.webp', (string) $result->avatar_path);

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
    }

    #[Test]
    public function it_writes_a_square_version_at_one_and_two_times_the_displayed_size(): void
    {
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

            $this->assertEquals($pixels, $size[0]);
            $this->assertEquals($pixels, $size[1]);
            $this->assertEquals('image/webp', $size['mime']);
        }

        $this->assertEquals([96, 192], Staff::avatarPixelSizes());
    }

    #[Test]
    public function it_removes_the_files_of_the_previous_avatar(): void
    {
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

        $this->assertNotEquals($firstPath, $second->avatar_path);

        foreach (Staff::avatarPixelSizes() as $pixels) {
            Storage::disk('local')->assertMissing($firstPath.'_'.$pixels.'.webp');
            Storage::disk('local')->assertExists($second->avatarVariantPath($pixels));
        }
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_an_image_we_accept(): void
    {
        $this->expectException(InvalidArgumentException::class);

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
    }

    #[Test]
    public function it_refuses_a_file_larger_than_five_megabytes(): void
    {
        $this->expectException(InvalidArgumentException::class);

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
    }

    #[Test]
    public function it_throws_when_the_author_may_only_change_their_own_avatar(): void
    {
        $this->expectException(ModelNotFoundException::class);

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
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

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
    }
}
