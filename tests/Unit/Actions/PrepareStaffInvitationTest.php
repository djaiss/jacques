<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PrepareStaffInvitation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrepareStaffInvitationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prepares_an_invitation_with_a_role(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $manager = $this->role($michael, Role::MANAGER);
        $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute', 'display_name' => null, 'email' => 'dwight@dundermifflin.com']);

        $invitation = new PrepareStaffInvitation(author: $michael, staff: $dwight, role: $manager)->execute();

        $this->assertInstanceOf(StaffInvitation::class, $invitation);
        $this->assertDatabaseHas('staff_invitations', ['staff_id' => $dwight->id, 'role_id' => $manager->id, 'author_id' => $michael->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffInvitationPrepared
                && $job->parameters === ['name' => 'Dwight Schrute', 'role' => $manager->name],
        );
    }

    #[Test]
    public function it_changes_the_role_of_the_invitation_already_prepared(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);
        StaffInvitation::factory()->create(['staff_id' => $dwight->id, 'role_id' => $this->role($michael, Role::STAFF)->id]);
        $manager = $this->role($michael, Role::MANAGER);

        new PrepareStaffInvitation(author: $michael, staff: $dwight, role: $manager)->execute();

        $this->assertDatabaseCount('staff_invitations', 1);
        $this->assertEquals($manager->id, $dwight->invitation()->value('role_id'));
    }

    #[Test]
    public function it_throws_without_an_email_address(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $michael = $this->owner();
        $kevin = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => null]);

        new PrepareStaffInvitation(author: $michael, staff: $kevin, role: $this->role($michael, Role::STAFF))->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_already_has_an_account(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $michael = $this->owner();
        $jim = Staff::factory()->create([
            'company_id' => $michael->company_id,
            'user_id' => User::factory()->create(['company_id' => $michael->company_id])->id,
            'email' => 'jim@dundermifflin.com',
        ]);

        new PrepareStaffInvitation(author: $michael, staff: $jim, role: $this->role($michael, Role::STAFF))->execute();
    }

    #[Test]
    public function it_throws_when_the_role_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->owner();
        $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);

        new PrepareStaffInvitation(author: $michael, staff: $dwight, role: Role::factory()->create())->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_roles(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->owner();
        $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $michael->company_id]), Role::RECEPTIONIST);
        $dwight = Staff::factory()->create(['company_id' => $michael->company_id, 'email' => 'dwight@dundermifflin.com']);

        new PrepareStaffInvitation(author: $pam, staff: $dwight, role: $this->role($michael, Role::STAFF))->execute();
    }

    private function owner(): User
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

        return $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    }

    private function role(User $user, string $slug): Role
    {
        return Role::query()->where('company_id', $user->company_id)->where('slug', $slug)->firstOrFail();
    }
}
