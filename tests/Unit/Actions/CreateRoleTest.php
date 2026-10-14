<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateRole;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_role_and_what_it_may_do(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

        $role = new CreateRole(
            author: $author,
            company: $company,
            name: 'Regional manager',
            grants: [
                ['permission' => PermissionEnum::EmployeeView, 'scope' => ScopeEnum::Company],
                ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Self],
                ['permission' => PermissionEnum::ReportView, 'scope' => ScopeEnum::Location],
            ],
            description: 'Runs the Scranton branch.',
        )->execute();

        $this->assertInstanceOf(Role::class, $role);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'company_id' => $company->id,
            'name' => 'Regional manager',
            'description' => 'Runs the Scranton branch.',
            'slug' => 'regional-manager',
            'is_default' => false,
            'is_editable' => true,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission' => PermissionEnum::EmployeeView->value,
            'scope' => ScopeEnum::Company->value,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission' => PermissionEnum::EmployeeUpdate->value,
            'scope' => ScopeEnum::Self->value,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission' => PermissionEnum::ReportView->value,
            'scope' => ScopeEnum::Location->value,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleCreated
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === [
                    'name' => 'Regional manager',
                    'permissions' => 'employee.view:company, employee.update:self, report.view:location',
                ],
        );
    }

    #[Test]
    public function it_makes_the_slug_unique_within_the_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

        $first = new CreateRole(author: $author, company: $company, name: 'Regional manager')->execute();
        $second = new CreateRole(author: $author, company: $company, name: 'Regional manager')->execute();

        $this->assertEquals('regional-manager', $first->slug);
        $this->assertEquals('regional-manager-2', $second->slug);
    }

    #[Test]
    public function it_lets_two_companies_use_the_same_slug(): void
    {
        Queue::fake();

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();

        $first = new CreateRole(
            author: $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage),
            company: $dunderMifflin,
            name: 'Regional manager',
        )->execute();

        $second = new CreateRole(
            author: $this->grant(User::factory()->create(['company_id' => $michaelScottPaperCompany->id]), PermissionEnum::RoleManage),
            company: $michaelScottPaperCompany,
            name: 'Regional manager',
        )->execute();

        $this->assertEquals('regional-manager', $first->slug);
        $this->assertEquals('regional-manager', $second->slug);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_administer_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $member = User::factory()->create(['company_id' => $company->id]);

        new CreateRole(author: $member, company: $company, name: 'Regional manager')->execute();
    }

    #[Test]
    public function it_throws_when_the_company_belongs_to_somebody_else(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);

        new CreateRole(
            author: $author,
            company: $michaelScottPaperCompany,
            name: 'Regional manager',
        )->execute();
    }

    #[Test]
    public function it_throws_when_a_company_wide_permission_is_granted_at_self_scope(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

        new CreateRole(
            author: $author,
            company: $company,
            name: 'Regional manager',
            grants: [
                ['permission' => PermissionEnum::CompanyManage, 'scope' => ScopeEnum::Self],
            ],
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_same_permission_is_granted_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

        new CreateRole(
            author: $author,
            company: $company,
            name: 'Regional manager',
            grants: [
                ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Self],
                ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Company],
            ],
        )->execute();
    }
}
