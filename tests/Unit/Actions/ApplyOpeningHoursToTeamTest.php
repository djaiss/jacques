<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ApplyOpeningHoursToTeam;
use App\Enums\PermissionEnum;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplyOpeningHoursToTeamTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_opening_hours_to_every_bookable_member_who_follows_them(): void
    {
        Queue::fake();

        $scranton = $this->openScranton();
        $michael = $this->author($scranton);
        $michaelStaff = $this->member($scranton);
        $jim = $this->member($scranton, customHours: true);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 2, 'start_time' => '10:00:00']);
        $pam = $this->member($scranton, attributes: ['is_bookable' => false]);

        $team = new ApplyOpeningHoursToTeam(author: $michael, location: $scranton, overrideCustomHours: false)->execute();

        $this->assertEquals([$michaelStaff->id], $team->pluck('id')->all());
        $this->assertEquals(['09:00:00', '13:00:00'], $michaelStaff->workingHours()->orderBy('start_time')->pluck('start_time')->all());
        $this->assertEquals(['10:00:00'], $jim->workingHours()->pluck('start_time')->all());
        $this->assertFalse($pam->workingHours()->exists());
    }

    #[Test]
    public function it_overrides_custom_hours_when_asked(): void
    {
        Queue::fake();

        $scranton = $this->openScranton();
        $michael = $this->author($scranton);
        $jim = $this->member($scranton, customHours: true);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 0]);

        new ApplyOpeningHoursToTeam(author: $michael, location: $scranton, overrideCustomHours: true)->execute();

        $this->assertEquals([6, 6], $jim->workingHours()->pluck('day_of_week')->all());
        $this->assertTrue((bool) $jim->locations()->first()->pivot->follows_opening_hours);
    }

    #[Test]
    public function it_leaves_out_inactive_members_and_paused_assignments(): void
    {
        Queue::fake();

        $scranton = $this->openScranton();
        $michael = $this->author($scranton);
        $toby = $this->member($scranton, attributes: ['is_active' => false]);
        $ryan = Staff::factory()->create(['company_id' => $scranton->company_id]);
        LocationStaff::factory()->paused()->create(['location_id' => $scranton->id, 'staff_id' => $ryan->id]);

        $team = new ApplyOpeningHoursToTeam(author: $michael, location: $scranton, overrideCustomHours: true)->execute();

        $this->assertTrue($team->isEmpty());
        $this->assertFalse($toby->workingHours()->exists());
        $this->assertFalse($ryan->workingHours()->exists());
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $nashua = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);

        new ApplyOpeningHoursToTeam(author: $michael, location: $nashua, overrideCustomHours: false)->execute();
    }

    private function openScranton(): Location
    {
        $scranton = Location::factory()->create(['name' => 'Scranton']);
        LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 6, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 6, 'start_time' => '13:00:00', 'end_time' => '17:00:00']);

        return $scranton;
    }

    private function author(Location $location): User
    {
        $user = User::factory()->create(['company_id' => $location->company_id]);
        $this->grant($user, PermissionEnum::LocationManage);

        return $this->grant($user, PermissionEnum::ScheduleManage);
    }

    /** @param array<string, mixed> $attributes */
    private function member(Location $location, bool $customHours = false, array $attributes = []): Staff
    {
        $staff = Staff::factory()->create(['company_id' => $location->company_id, ...$attributes]);
        $staff->locations()->attach($location->id, ['follows_opening_hours' => ! $customHours]);

        return $staff;
    }
}
