<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateRole;
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
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_role_and_replaces_what_it_may_do(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman', 'slug' => 'salesman']);
        RolePermission::factory()->create([
            'role_id' => $role->id,
            'permission' => PermissionEnum::EmployeeView,
            'scope' => ScopeEnum::Company,
        ]);

        $result = new UpdateRole(
            author: $author,
            role: $role,
            name: 'Assistant regional manager',
            grants: [
                ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Self],
            ],
            description: 'Assistant to the regional manager, really.',
        )->execute();

        $this->assertInstanceOf(Role::class, $result);

        $role->refresh();

        $this->assertEquals('Assistant regional manager', $role->name);
        $this->assertEquals('Assistant to the regional manager, really.', $role->description);
        $this->assertEquals('salesman', $role->slug);
        $this->assertCount(1, $role->permissions);
        $this->assertEquals(PermissionEnum::EmployeeUpdate, $role->permissions->first()->permission);
        $this->assertEquals(ScopeEnum::Self, $role->permissions->first()->scope);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleUpdated
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === [
                    'name' => 'Assistant regional manager',
                    'permissions' => 'employee.update:self',
                ],
        );
    }

    #[Test]
    public function it_takes_every_permission_away_when_none_are_given(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);
        RolePermission::factory()->create(['role_id' => $role->id]);

        new UpdateRole(author: $author, role: $role, name: 'Salesman')->execute();

        $this->assertCount(0, $role->refresh()->permissions);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_administer_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $member = User::factory()->create(['company_id' => $company->id]);
        $role = Role::factory()->create(['company_id' => $company->id]);

        new UpdateRole(author: $member, role: $role, name: 'Salesman')->execute();
    }

    #[Test]
    public function it_throws_when_the_role_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $michaelScottPaperCompany->id]);

        new UpdateRole(author: $author, role: $role, name: 'Salesman')->execute();
    }

    #[Test]
    public function it_throws_when_the_role_is_locked(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->locked()->create(['company_id' => $company->id]);

        new UpdateRole(author: $author, role: $role, name: 'Salesman')->execute();
    }

    #[Test]
    public function it_throws_when_a_company_wide_permission_is_granted_at_self_scope(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);

        new UpdateRole(
            author: $author,
            role: $role,
            name: 'Salesman',
            grants: [
                ['permission' => PermissionEnum::RoleManage, 'scope' => ScopeEnum::Self],
            ],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_same_permission_is_granted_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $role = Role::factory()->create(['company_id' => $company->id]);

        new UpdateRole(
            author: $author,
            role: $role,
            name: 'Salesman',
            grants: [
                ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Self],
                ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Company],
            ],
        )->execute();
    }
}
