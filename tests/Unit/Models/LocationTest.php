<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\LocationStaff;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create();
        $location = Location::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($location->company()->exists());
        $this->assertEquals($company->id, $location->company->id);
    }

    #[Test]
    public function it_belongs_to_many_staff_members_with_the_state_of_each_assignment(): void
    {
        $location = Location::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $location->company_id]);
        LocationStaff::factory()->paused()->create(['location_id' => $location->id, 'staff_id' => $dwight->id]);

        $this->assertTrue($location->staff()->exists());
        $this->assertEquals($dwight->id, $location->staff->first()->id);
        $this->assertFalse((bool) $location->staff->first()->pivot->is_active);
    }

    #[Test]
    public function it_has_many_location_services(): void
    {
        $location = Location::factory()->create();
        LocationService::factory()->create(['location_id' => $location->id]);

        $this->assertTrue($location->locationServices()->exists());
    }

    #[Test]
    public function it_belongs_to_many_services(): void
    {
        $location = Location::factory()->create();
        $haircut = Service::factory()->create(['company_id' => $location->company_id, 'name' => ['en' => 'Haircut']]);
        $location->services()->attach($haircut->id);

        $this->assertTrue($location->services()->exists());
        $this->assertEquals(['en' => 'Haircut'], $location->services->first()->name);
        $this->assertTrue((bool) $location->services->first()->pivot->is_active);
    }

    #[Test]
    public function it_knows_whether_it_has_been_archived(): void
    {
        $open = Location::factory()->make();
        $closed = Location::factory()->archived()->make();

        $this->assertFalse($open->isArchived());
        $this->assertTrue($closed->isArchived());
    }

    #[Test]
    public function it_casts_the_day_it_was_archived_and_the_headquarters_flag(): void
    {
        $location = Location::factory()->archived()->primary()->create();

        $this->assertInstanceOf(Carbon::class, $location->archived_at);
        $this->assertIsBool($location->is_headquarters);
    }
}
