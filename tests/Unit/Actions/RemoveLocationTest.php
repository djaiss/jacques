<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffAbsence;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_somebody_off_a_location(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton']);
        $dwight = Staff::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'display_name' => null,
        ]);
        $dwight->locations()->attach($scranton);

        $result = new RemoveLocation(author: $author, staff: $dwight, location: $scranton)->execute();

        $this->assertInstanceOf(Staff::class, $result);
        $this->assertDatabaseMissing('location_staff', [
            'staff_id' => $dwight->id,
            'location_id' => $scranton->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffRemovedFromLocation
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === [
                    'name' => 'Scranton',
                    'staff' => 'Dwight Schrute',
                ],
        );
    }

    #[Test]
    public function it_forgets_the_services_they_performed_at_that_location_only(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $atScranton = LocationService::factory()->create(['location_id' => $scranton->id]);
        $atStamford = LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $atScranton->service_id]);
        $jim = Staff::factory()->create(['company_id' => $company->id]);
        $jim->locations()->attach([$scranton->id, $stamford->id]);
        $jim->locationServices()->attach([$atScranton->id, $atStamford->id]);

        new RemoveLocation(author: $author, staff: $jim, location: $scranton)->execute();

        $this->assertEquals([$atStamford->id], $jim->locationServices()->pluck('location_services.id')->all());
    }

    #[Test]
    public function it_throws_when_the_author_may_not_hand_out_roles(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);

        new RemoveLocation(author: $michael, staff: $dwight, location: $scranton)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stranger = Staff::factory()->create();

        new RemoveLocation(author: $author, staff: $stranger, location: $scranton)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $nashua = Location::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);

        new RemoveLocation(author: $author, staff: $dwight, location: $nashua)->execute();
    }

    #[Test]
    public function it_forgets_the_working_hours_of_the_staff_member_at_the_location(): void
    {
        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $dwight->locations()->attach([$scranton->id, $stamford->id]);
        $atScranton = StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $scranton->id]);
        $atStamford = StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $stamford->id]);

        new RemoveLocation(author: $author, staff: $dwight, location: $scranton)->execute();

        $this->assertModelMissing($atScranton);
        $this->assertModelExists($atStamford);
    }

    #[Test]
    public function it_forgets_the_absences_at_the_location_and_keeps_the_ones_everywhere(): void
    {
        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $angela = Staff::factory()->create(['company_id' => $company->id]);
        $angela->locations()->attach($scranton);
        $atScranton = StaffAbsence::factory()->create(['staff_id' => $angela->id, 'location_id' => $scranton->id]);
        $everywhere = StaffAbsence::factory()->create(['staff_id' => $angela->id, 'location_id' => null]);

        new RemoveLocation(author: $author, staff: $angela, location: $scranton)->execute();

        $this->assertModelMissing($atScranton);
        $this->assertModelExists($everywhere);
    }
}
