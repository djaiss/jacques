<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateStaffInformation;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateStaffInformationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_information_of_the_staff_member_signed_in(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
        $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

        $result = new UpdateStaffInformation(
            author: $user,
            staff: $staff,
            firstName: 'Dwight',
            lastName: 'Schrute',
            displayName: 'Assistant to the Regional Manager',
            email: 'Dwight.Schrute@DunderMifflin.com',
            phone: null,
            jobTitle: null,
            bio: null,
            isBookable: true,
        )->execute();

        $this->assertInstanceOf(Staff::class, $result);

        $staff->refresh();

        $this->assertEquals('Dwight', $staff->first_name);
        $this->assertEquals('Schrute', $staff->last_name);
        $this->assertEquals('Assistant to the Regional Manager', $staff->display_name);
        $this->assertEquals('dwight.schrute@dundermifflin.com', $staff->email);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInformationUpdated
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_saves_the_phone_the_job_title_the_bio_and_whether_clients_can_book(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

        new UpdateStaffInformation(
            author: $user,
            staff: $staff,
            firstName: 'Stanley',
            lastName: 'Hudson',
            displayName: null,
            email: null,
            phone: '+1 570 555 0187',
            jobTitle: 'Salesman',
            bio: 'Did I stutter?',
            isBookable: false,
        )->execute();

        $staff->refresh();

        $this->assertEquals('+1 570 555 0187', $staff->phone);
        $this->assertEquals('Salesman', $staff->job_title);
        $this->assertEquals('Did I stutter?', $staff->bio);
        $this->assertFalse($staff->is_bookable);
    }

    #[Test]
    public function it_updates_a_colleague_at_company_scope(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $jim = Staff::factory()->create(['company_id' => $company->id]);
        $author = User::factory()->create(['company_id' => $company->id]);
        $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

        new UpdateStaffInformation(
            author: $author,
            staff: $jim,
            firstName: 'Jim',
            lastName: 'Halpert',
            displayName: null,
            email: null,
            phone: null,
            jobTitle: null,
            bio: null,
            isBookable: true,
        )->execute();

        $this->assertEquals('Jim', $jim->refresh()->first_name);
    }

    #[Test]
    public function it_clears_the_optional_fields_when_they_are_null(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'display_name' => 'Big Tuna',
            'email' => 'jim.halpert@dundermifflin.com',
            'phone' => '+1 570 555 0100',
            'job_title' => 'Salesman',
            'bio' => 'Pranks Dwight.',
        ]);
        $this->grant($user, PermissionEnum::StaffUpdate, ScopeEnum::Self);

        new UpdateStaffInformation(
            author: $user,
            staff: $staff,
            firstName: 'Jim',
            lastName: null,
            displayName: null,
            email: null,
            phone: null,
            jobTitle: null,
            bio: null,
            isBookable: true,
        )->execute();

        $staff->refresh();

        $this->assertNull($staff->last_name);
        $this->assertNull($staff->display_name);
        $this->assertNull($staff->email);
        $this->assertNull($staff->phone);
        $this->assertNull($staff->job_title);
        $this->assertNull($staff->bio);
    }

    #[Test]
    public function it_throws_when_the_author_may_only_edit_themselves(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $author->id, 'first_name' => 'Angela']);
        $oscar = Staff::factory()->create(['company_id' => $company->id]);
        $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Self);

        new UpdateStaffInformation(
            author: $author,
            staff: $oscar,
            firstName: 'Oscar',
            lastName: 'Martinez',
            displayName: null,
            email: null,
            phone: null,
            jobTitle: null,
            bio: null,
            isBookable: true,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_holds_no_role(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $author->id]);

        new UpdateStaffInformation(
            author: $author,
            staff: $staff,
            firstName: 'Creed',
            lastName: 'Bratton',
            displayName: null,
            email: null,
            phone: null,
            jobTitle: null,
            bio: null,
            isBookable: true,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
        $this->grant($author, PermissionEnum::StaffUpdate, ScopeEnum::Company);

        new UpdateStaffInformation(
            author: $author,
            staff: $stranger,
            firstName: 'Creed',
            lastName: 'Bratton',
            displayName: null,
            email: null,
            phone: null,
            jobTitle: null,
            bio: null,
            isBookable: true,
        )->execute();
    }
}
