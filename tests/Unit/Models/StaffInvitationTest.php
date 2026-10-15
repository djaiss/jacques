<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\StaffInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffInvitationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_staff_member(): void
    {
        $invitation = StaffInvitation::factory()->create();

        $this->assertTrue($invitation->staff()->exists());
    }

    #[Test]
    public function it_belongs_to_a_role(): void
    {
        $invitation = StaffInvitation::factory()->create();

        $this->assertTrue($invitation->role()->exists());
    }

    #[Test]
    public function it_belongs_to_the_user_who_prepared_it(): void
    {
        $invitation = StaffInvitation::factory()->create();

        $this->assertTrue($invitation->author()->exists());
    }

    #[Test]
    public function it_keeps_no_author_once_their_account_is_deleted(): void
    {
        $invitation = StaffInvitation::factory()->create();

        $invitation->author->forceDelete();

        $this->assertNull($invitation->refresh()->author_id);
    }
}
