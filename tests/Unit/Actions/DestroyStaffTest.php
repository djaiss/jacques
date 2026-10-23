<?php

declare(strict_types=1);

use App\Actions\DestroyStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffAbsence;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('deletes a staff member and everything attached to them', function () {
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
});

it('deletes the avatar files', function () {
    Storage::fake((string) config('filesystems.default'));

    $stanley = Staff::factory()->create(['avatar_path' => 'avatars/1/stanley']);
    Storage::disk((string) config('filesystems.default'))->put('avatars/1/stanley_96.webp', 'image');
    Storage::disk((string) config('filesystems.default'))->put('avatars/1/stanley_192.webp', 'image');
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $stanley->company_id]), Role::OWNER);

    new DestroyStaff(author: $michael, staff: $stanley)->execute();

    Storage::disk((string) config('filesystems.default'))->assertMissing('avatars/1/stanley_96.webp');
    Storage::disk((string) config('filesystems.default'))->assertMissing('avatars/1/stanley_192.webp');
});

it('refuses to delete a staff member with appointments', function () {
    $jim = Staff::factory()->create(['first_name' => 'Jim']);
    Appointment::factory()->create(['company_id' => $jim->company_id, 'staff_id' => $jim->id]);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $jim->company_id]), Role::OWNER);

    new DestroyStaff(author: $michael, staff: $jim)->execute();
})->throws(ValidationException::class, 'This person has appointments and cannot be deleted. Deactivate them instead.');

it('throws when a manager deletes a staff member', function () {
    $ryan = Staff::factory()->create();
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $ryan->company_id]), Role::MANAGER);

    new DestroyStaff(author: $dwight, staff: $ryan)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::StaffDelete);

    new DestroyStaff(author: $michael, staff: Staff::factory()->create())->execute();
})->throws(ModelNotFoundException::class);
