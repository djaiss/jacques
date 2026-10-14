<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Location;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $assignment = UserLocation::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($assignment->user()->exists());
        $this->assertEquals($user->id, $assignment->user->id);
    }

    #[Test]
    public function it_belongs_to_a_location(): void
    {
        $location = Location::factory()->create();
        $assignment = UserLocation::factory()->create(['location_id' => $location->id]);

        $this->assertTrue($assignment->location()->exists());
        $this->assertEquals($location->id, $assignment->location->id);
    }

    #[Test]
    public function it_says_when_the_person_was_assigned(): void
    {
        $assignment = UserLocation::factory()->create();

        $this->assertNotNull($assignment->created_at);
        $this->assertEqualsWithDelta(now()->timestamp, $assignment->created_at->timestamp, 2);
    }
}
