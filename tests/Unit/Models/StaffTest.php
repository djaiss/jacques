<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\LocationStaff;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $staff = Staff::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($staff->company()->exists());
        $this->assertEquals('Dunder Mifflin', $staff->company->name);
    }

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $staff = Staff::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($staff->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $staff->user->email);
    }

    #[Test]
    public function it_has_no_user_when_the_staff_member_has_no_account(): void
    {
        $staff = Staff::factory()->create();

        $this->assertFalse($staff->user()->exists());
    }

    #[Test]
    public function it_belongs_to_many_locations_with_the_state_of_each_assignment(): void
    {
        $staff = Staff::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $staff->company_id]);
        $stamford = Location::factory()->create(['company_id' => $staff->company_id]);
        LocationStaff::factory()->create(['location_id' => $scranton->id, 'staff_id' => $staff->id]);
        LocationStaff::factory()->paused()->create(['location_id' => $stamford->id, 'staff_id' => $staff->id]);

        $locations = $staff->locations()->get()->keyBy('id');

        $this->assertTrue($staff->locations()->exists());
        $this->assertCount(2, $locations);
        $this->assertTrue((bool) $locations[$scranton->id]->pivot->is_active);
        $this->assertFalse((bool) $locations[$stamford->id]->pivot->is_active);
    }

    #[Test]
    public function it_leaves_paused_assignments_out_of_the_active_locations(): void
    {
        $staff = Staff::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $staff->company_id]);
        $stamford = Location::factory()->create(['company_id' => $staff->company_id]);
        LocationStaff::factory()->create(['location_id' => $scranton->id, 'staff_id' => $staff->id]);
        LocationStaff::factory()->paused()->create(['location_id' => $stamford->id, 'staff_id' => $staff->id]);

        $this->assertEquals([$scranton->id], $staff->activeLocations()->pluck('locations.id')->all());
    }

    #[Test]
    public function it_belongs_to_many_location_services(): void
    {
        $staff = Staff::factory()->create();
        $locationService = LocationService::factory()->create();
        $staff->locationServices()->attach($locationService);

        $this->assertTrue($staff->locationServices()->exists());
        $this->assertEquals([$locationService->id], $staff->locationServices()->pluck('location_services.id')->all());
    }

    #[Test]
    public function it_returns_the_legal_name_as_the_name_when_the_staff_member_goes_by_no_other_name(): void
    {
        $staff = Staff::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => null,
        ]);

        $this->assertEquals('Michael Scott', $staff->name);
    }

    #[Test]
    public function it_returns_the_display_name_as_the_name_when_the_staff_member_goes_by_another_name(): void
    {
        $staff = Staff::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'display_name' => 'Assistant to the Regional Manager',
        ]);

        $this->assertEquals('Assistant to the Regional Manager', $staff->name);
    }

    #[Test]
    public function it_returns_the_full_name(): void
    {
        $staff = Staff::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'display_name' => 'Pamela',
        ]);

        $this->assertEquals('Pam Beesly', $staff->full_name);
    }

    #[Test]
    public function it_returns_the_first_name_alone_as_the_full_name_when_there_is_no_last_name(): void
    {
        $staff = Staff::factory()->create([
            'first_name' => 'Creed',
            'last_name' => null,
            'display_name' => null,
        ]);

        $this->assertEquals('Creed', $staff->full_name);
        $this->assertEquals('Creed', $staff->name);
    }

    #[Test]
    public function it_has_an_avatar_when_a_path_was_stored(): void
    {
        $staff = Staff::factory()->create(['avatar_path' => 'avatars/1/stanley']);

        $this->assertTrue($staff->hasAvatar());
    }

    #[Test]
    public function it_has_no_avatar_by_default(): void
    {
        $staff = Staff::factory()->create();

        $this->assertFalse($staff->hasAvatar());
    }

    #[Test]
    public function it_names_each_version_of_the_avatar_after_its_size(): void
    {
        $staff = Staff::factory()->create(['avatar_path' => 'avatars/1/stanley']);

        $this->assertEquals('avatars/1/stanley_96.webp', $staff->avatarVariantPath(96));
        $this->assertEquals('avatars/1/stanley_192.webp', $staff->avatarVariantPath(192));
    }

    #[Test]
    public function it_writes_the_displayed_size_and_twice_it(): void
    {
        $this->assertEquals([96, 192], Staff::avatarPixelSizes());
    }
}
