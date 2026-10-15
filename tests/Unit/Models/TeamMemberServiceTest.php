<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\LocationService;
use App\Models\TeamMemberService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamMemberServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $assignment = TeamMemberService::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($assignment->user()->exists());
        $this->assertEquals($user->id, $assignment->user->id);
    }

    #[Test]
    public function it_belongs_to_a_location_service(): void
    {
        $locationService = LocationService::factory()->create();
        $assignment = TeamMemberService::factory()->create(['location_service_id' => $locationService->id]);

        $this->assertTrue($assignment->locationService()->exists());
        $this->assertEquals($locationService->id, $assignment->locationService->id);
    }
}
