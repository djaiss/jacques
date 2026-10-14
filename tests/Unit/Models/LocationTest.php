<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Location;
use App\Models\User;
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
    public function it_has_many_users(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['company_id' => $location->company_id]);
        $location->users()->attach($user);

        $this->assertTrue($location->users()->exists());
        $this->assertEquals($user->id, $location->users->first()->id);
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
