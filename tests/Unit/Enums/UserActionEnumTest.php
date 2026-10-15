<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserActionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserActionEnumTest extends TestCase
{
    #[Test]
    public function it_describes_every_action(): void
    {
        foreach (UserActionEnum::cases() as $action) {
            $this->assertNotEmpty($action->description());
        }
    }

    #[Test]
    public function it_describes_an_action_with_the_parameters_it_was_logged_with(): void
    {
        $this->assertEquals('Changed the languages of the salon to :languages', UserActionEnum::CompanyLanguagesUpdated->description());
        $this->assertEquals('Updated the profile of :name', UserActionEnum::EmployeeInformationUpdated->description());
        $this->assertEquals('Changed the avatar of :name', UserActionEnum::EmployeeAvatarUpdated->description());
        $this->assertEquals('Removed the avatar of :name', UserActionEnum::EmployeeAvatarDeleted->description());
        $this->assertEquals('Assigned :email to the location called :name', UserActionEnum::UserAssignedToLocation->description());
        $this->assertEquals('Offered the service called :name at the location called :location', UserActionEnum::LocationServiceCreated->description());
        $this->assertEquals('Let :email perform the service called :name at the location called :location', UserActionEnum::TeamMemberServiceAssigned->description());
    }
}
