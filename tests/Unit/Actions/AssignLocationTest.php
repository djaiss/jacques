<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AssignLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_assigns_somebody_to_a_location(): void
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

        $result = new AssignLocation(author: $author, staff: $dwight, location: $scranton)->execute();

        $this->assertInstanceOf(Staff::class, $result);
        $this->assertDatabaseHas('location_staff', [
            'staff_id' => $dwight->id,
            'location_id' => $scranton->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffAssignedToLocation
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === [
                    'name' => 'Scranton',
                    'staff' => 'Dwight Schrute',
                ],
        );
    }

    #[Test]
    public function it_changes_nothing_when_they_are_already_assigned(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);
        $dwight->locations()->attach($scranton);

        new AssignLocation(author: $author, staff: $dwight, location: $scranton)->execute();

        $this->assertEquals(1, $dwight->locations()->count());
    }

    #[Test]
    public function it_throws_when_the_author_may_not_hand_out_roles(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = Staff::factory()->create(['company_id' => $company->id]);

        new AssignLocation(author: $michael, staff: $dwight, location: $scranton)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stranger = Staff::factory()->create();

        new AssignLocation(author: $author, staff: $stranger, location: $scranton)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $nashua = Location::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $company->id]);

        new AssignLocation(author: $author, staff: $dwight, location: $nashua)->execute();
    }
}
