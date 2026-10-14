<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEmployee;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateEmployeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_employee(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->grant($user, PermissionEnum::EmployeeCreate);

        $employee = new CreateEmployee(
            author: $user,
            company: $company,
            firstName: 'Michael',
            lastName: 'Scott',
            employeeNumber: 'EMP-0001',
            displayName: 'The World Best Boss',
            workEmail: 'michael.scott@dundermifflin.com',
        )->execute();

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'company_id' => $company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => 'The World Best Boss',
            'work_email' => 'michael.scott@dundermifflin.com',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmployeeCreated
                && $job->company->id === $company->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'The World Best Boss'],
        );
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $stranger = User::factory()->create();
        $this->grant($stranger, PermissionEnum::EmployeeCreate);

        new CreateEmployee(
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
        $this->grant($member, PermissionEnum::EmployeeView);

        new CreateEmployee(
            author: $member,
            company: $company,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();
    }
}
