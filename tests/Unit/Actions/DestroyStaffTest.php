<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffAbsence;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyStaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_staff_member_and_everything_attached_to_them(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create();
        $account = User::factory()->create(['company_id' => $scranton->company_id]);
        $creed = Staff::factory()->create(['company_id' => $scranton->company_id, 'user_id' => $account->id, 'first_name' => 'Creed', 'last_name' => 'Bratton']);
        $creed->locations()->attach($scranton);
        $creed->locationServices()->attach(LocationService::factory()->create(['location_id' => $scranton->id]));
        $workingHour = StaffWorkingHour::factory()->create(['staff_id' => $creed->id, 'location_id' => $scranton->id]);
        $absence = StaffAbsence::factory()->create(['staff_id' => $creed->id]);
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::OWNER);

        new DestroyStaff(author: $michael, staff: $creed)->execute();

        $this->assertModelMissing($creed);
        $this->assertModelMissing($workingHour);
        $this->assertModelMissing($absence);
        $this->assertDatabaseMissing('location_staff', ['staff_id' => $creed->id]);
        $this->assertDatabaseMissing('staff_location_services', ['staff_id' => $creed->id]);
        $this->assertModelExists($account);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffDeleted
                && $job->parameters === ['name' => 'Creed Bratton'],
        );
    }

    #[Test]
    public function it_deletes_the_avatar_files(): void
    {
        Storage::fake((string) config('filesystems.default'));

        $stanley = Staff::factory()->create(['avatar_path' => 'avatars/1/stanley']);
        Storage::disk((string) config('filesystems.default'))->put('avatars/1/stanley_96.webp', 'image');
        Storage::disk((string) config('filesystems.default'))->put('avatars/1/stanley_192.webp', 'image');
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $stanley->company_id]), Role::OWNER);

        new DestroyStaff(author: $michael, staff: $stanley)->execute();

        Storage::disk((string) config('filesystems.default'))->assertMissing('avatars/1/stanley_96.webp');
        Storage::disk((string) config('filesystems.default'))->assertMissing('avatars/1/stanley_192.webp');
    }

    #[Test]
    public function it_throws_when_a_manager_deletes_a_staff_member(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ryan = Staff::factory()->create();
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $ryan->company_id]), Role::MANAGER);

        new DestroyStaff(author: $dwight, staff: $ryan)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::StaffDelete);

        new DestroyStaff(author: $michael, staff: Staff::factory()->create())->execute();
    }
}
