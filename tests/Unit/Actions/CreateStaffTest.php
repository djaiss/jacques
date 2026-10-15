<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateStaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_staff_member(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::StaffCreate);

        $staff = new CreateStaff(
            author: $user,
            company: $company,
            firstName: 'Michael',
            lastName: 'Scott',
            displayName: 'The World Best Boss',
            email: 'Michael.Scott@DunderMifflin.com',
        )->execute();

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'company_id' => $company->id,
            'user_id' => null,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => 'The World Best Boss',
            'email' => 'michael.scott@dundermifflin.com',
            'is_bookable' => true,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffCreated
                && $job->company->id === $company->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'The World Best Boss'],
        );
    }

    #[Test]
    public function it_saves_every_optional_field(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::StaffCreate);

        $staff = new CreateStaff(
            author: $user,
            company: $company,
            firstName: 'Pam',
            lastName: 'Beesly',
            phone: '+1 570 555 0142',
            jobTitle: 'Receptionist',
            bio: 'Draws the watercolours in the lobby.',
            isBookable: false,
        )->execute();

        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'phone' => '+1 570 555 0142',
            'job_title' => 'Receptionist',
            'bio' => 'Draws the watercolours in the lobby.',
            'is_bookable' => false,
        ]);
    }

    #[Test]
    public function it_accepts_a_staff_member_without_a_last_name(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::StaffCreate);

        $staff = new CreateStaff(
            author: $user,
            company: $company,
            firstName: 'Creed',
        )->execute();

        $this->assertNull($staff->refresh()->last_name);
        $this->assertEquals('Creed', $staff->full_name);
    }

    #[Test]
    public function it_places_the_staff_member_after_the_last_one_of_the_same_company(): void
    {
        Queue::fake();

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        Staff::factory()->create(['company_id' => $dunderMifflin->id, 'position' => 1]);
        Staff::factory()->create(['company_id' => $dunderMifflin->id, 'position' => 4]);
        Staff::factory()->create(['company_id' => $michaelScottPaperCompany->id, 'position' => 9]);
        $user = User::factory()->create(['company_id' => $dunderMifflin->id]);
        $this->grant($user, PermissionEnum::StaffCreate);

        $staff = new CreateStaff(
            author: $user,
            company: $dunderMifflin,
            firstName: 'Kevin',
            lastName: 'Malone',
        )->execute();

        $this->assertEquals(5, $staff->refresh()->position);
    }

    #[Test]
    public function it_links_an_account_of_the_same_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $this->grant($author, PermissionEnum::StaffCreate);
        $account = User::factory()->create(['company_id' => $company->id]);

        $staff = new CreateStaff(
            author: $author,
            company: $company,
            firstName: 'Andy',
            lastName: 'Bernard',
            user: $account,
        )->execute();

        $this->assertEquals($account->id, $staff->refresh()->user_id);
        $this->assertEquals($staff->id, $account->refresh()->staff->id);
    }

    #[Test]
    public function it_throws_when_the_account_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $this->grant($author, PermissionEnum::StaffCreate);
        $stranger = User::factory()->create();

        new CreateStaff(
            author: $author,
            company: $company,
            firstName: 'Todd',
            lastName: 'Packer',
            user: $stranger,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_account_already_gives_access_to_a_staff_member(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $this->grant($author, PermissionEnum::StaffCreate);
        $account = User::factory()->create(['company_id' => $company->id]);
        Staff::factory()->create(['company_id' => $company->id, 'user_id' => $account->id]);

        new CreateStaff(
            author: $author,
            company: $company,
            firstName: 'Ryan',
            lastName: 'Howard',
            user: $account,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $stranger = User::factory()->create();
        $this->grant($stranger, PermissionEnum::StaffCreate);

        new CreateStaff(
            author: $stranger,
            company: $company,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_add_anybody(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $member = User::factory()->create(['company_id' => $company->id]);
        $this->grant($member, PermissionEnum::StaffView);

        new CreateStaff(
            author: $member,
            company: $company,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();
    }
}
