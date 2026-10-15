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
        $this->assertEquals('Changed the currencies of the salon to :currencies', UserActionEnum::CompanyCurrenciesUpdated->description());
        $this->assertEquals('Added :name to the company', UserActionEnum::StaffCreated->description());
        $this->assertEquals('Updated the profile of :name', UserActionEnum::StaffInformationUpdated->description());
        $this->assertEquals('Changed the avatar of :name', UserActionEnum::StaffAvatarUpdated->description());
        $this->assertEquals('Removed the avatar of :name', UserActionEnum::StaffAvatarDeleted->description());
        $this->assertEquals('Assigned :staff to the location called :name', UserActionEnum::StaffAssignedToLocation->description());
        $this->assertEquals('Took :staff off the location called :name', UserActionEnum::StaffRemovedFromLocation->description());
        $this->assertEquals('Paused the assignment of :staff to the location called :name', UserActionEnum::LocationStaffDeactivated->description());
        $this->assertEquals('Offered the service called :name at the location called :location', UserActionEnum::LocationServiceCreated->description());
        $this->assertEquals('Let :staff perform the service called :name at the location called :location', UserActionEnum::StaffLocationServiceAssigned->description());
        $this->assertEquals('Stopped :staff performing the service called :name at the location called :location', UserActionEnum::StaffLocationServiceRemoved->description());
    }
}
