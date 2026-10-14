<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateEmployeeInformation;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
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

class UpdateEmployeeInformationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_information_of_the_employee_signed_in(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        $result = new UpdateEmployeeInformation(
            author: $user,
            employee: $employee,
            firstName: 'Dwight',
            lastName: 'Schrute',
            displayName: 'Assistant to the Regional Manager',
            workEmail: 'Dwight.Schrute@DunderMifflin.com',
        )->execute();

        $this->assertInstanceOf(Employee::class, $result);

        $employee->refresh();

        $this->assertEquals('Dwight', $employee->first_name);
        $this->assertEquals('Schrute', $employee->last_name);
        $this->assertEquals('Assistant to the Regional Manager', $employee->display_name);
        $this->assertEquals('dwight.schrute@dundermifflin.com', $employee->work_email);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmployeeInformationUpdated
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_updates_a_colleague_at_company_scope(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $jim = Employee::factory()->create(['company_id' => $company->id]);
        $author = User::factory()->create(['company_id' => $company->id]);
        $this->grant($author, PermissionEnum::EmployeeUpdate, ScopeEnum::Company);

        new UpdateEmployeeInformation(
            author: $author,
            employee: $jim,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();

        $this->assertEquals('Jim', $jim->refresh()->first_name);
    }

    #[Test]
    public function it_clears_the_optional_fields_when_they_are_not_given(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'display_name' => 'Big Tuna',
            'work_email' => 'jim.halpert@dundermifflin.com',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        new UpdateEmployeeInformation(
            author: $user,
            employee: $employee,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();

        $employee->refresh();

        $this->assertNull($employee->display_name);
        $this->assertNull($employee->work_email);
    }

    #[Test]
    public function it_throws_when_the_author_may_only_edit_themselves(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $angela = Employee::factory()->create(['company_id' => $company->id]);
        $oscar = Employee::factory()->create(['company_id' => $company->id]);
        $author = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $angela->id,
        ]);
        $this->grant($author, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        new UpdateEmployeeInformation(
            author: $author,
            employee: $oscar,
            firstName: 'Oscar',
            lastName: 'Martinez',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_holds_no_role(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $author = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        new UpdateEmployeeInformation(
            author: $author,
            employee: $employee,
            firstName: 'Creed',
            lastName: 'Bratton',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_employee_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Employee::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
        $this->grant($author, PermissionEnum::EmployeeUpdate, ScopeEnum::Company);

        new UpdateEmployeeInformation(
            author: $author,
            employee: $stranger,
            firstName: 'Creed',
            lastName: 'Bratton',
        )->execute();
    }
}
