<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveRole;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_role_away_from_somebody(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman']);
        $dwight = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);
        $dwight->roles()->attach($role->id);

        $result = new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $dwight->id,
            'role_id' => $role->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleRemoved
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === [
                    'name' => 'Salesman',
                    'email' => 'dwight.schrute@dundermifflin.com',
                ],
        );
    }

    #[Test]
    public function it_changes_nothing_when_they_never_held_the_role(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);
        $dwight = User::factory()->create(['company_id' => $company->id]);

        new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

        $this->assertEquals(0, $dwight->roles()->count());
    }

    #[Test]
    public function it_takes_the_permissions_of_the_role_away_too(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => PermissionEnum::CompanyManage,
            'scope' => ScopeEnum::Company,
        ]);
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $dwight->roles()->attach($role->id);

        new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

        $this->assertFalse(
            $dwight->fresh()->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed(),
        );
    }

    #[Test]
    public function it_stops_letting_them_through_straight_away(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => PermissionEnum::CompanyManage,
            'scope' => ScopeEnum::Company,
        ]);
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $dwight->roles()->attach($role->id);

        $this->assertTrue(
            $dwight->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed(),
        );

        new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

        $this->assertFalse(
            $dwight->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed(),
        );
    }

    #[Test]
    public function it_throws_when_the_author_may_not_administer_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $member = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $dwight->roles()->attach($role->id);

        new RemoveRole(author: $member, user: $dwight, role: $role)->execute();
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);
        $stranger = User::factory()->create();

        new RemoveRole(author: $author, user: $stranger, role: $role)->execute();
    }

    #[Test]
    public function it_throws_when_the_role_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $dwight = User::factory()->create(['company_id' => $dunderMifflin->id]);

        new RemoveRole(author: $author, user: $dwight, role: $role)->execute();
    }

    #[Test]
    public function it_takes_the_owner_role_away_when_another_owner_remains(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        $jan = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

        new RemoveRole(author: $michael, user: $michael, role: $owner)->execute();

        $this->assertFalse($michael->isOwner());
        $this->assertTrue($jan->isOwner());
    }

    #[Test]
    public function it_throws_when_the_last_owner_would_lose_the_role(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

        new RemoveRole(author: $michael, user: $michael, role: $owner)->execute();
    }

    #[Test]
    public function it_does_not_count_an_owner_who_has_been_deactivated(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
        $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id, 'is_active' => false]), Role::OWNER);
        $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

        new RemoveRole(author: $michael, user: $michael, role: $owner)->execute();
    }
}
