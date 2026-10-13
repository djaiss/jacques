<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Profile;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\ViewModels\Settings\Account\Profile\ProfileViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_details_of_the_employee(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
            'display_name' => null,
            'work_email' => 'stanley.hudson@dundermifflin.com',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $viewModel = new ProfileViewModel(user: $user, employee: $employee);

        $this->assertEquals([
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
            'display_name' => null,
            'work_email' => 'stanley.hudson@dundermifflin.com',
        ], $viewModel->details());
    }

    #[Test]
    public function it_gives_the_name_the_employee_goes_by(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
            'display_name' => 'Big Tuna',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $viewModel = new ProfileViewModel(user: $user, employee: $employee);

        $this->assertEquals('Big Tuna', $viewModel->name());
    }

    #[Test]
    public function it_falls_back_to_the_email_when_there_is_no_employee_record(): void
    {
        $user = User::factory()->create([
            'employee_id' => null,
            'email' => 'accountant@vancerefrigeration.com',
        ]);

        $viewModel = new ProfileViewModel(user: $user, employee: null);

        $this->assertEquals('accountant@vancerefrigeration.com', $viewModel->name());
        $this->assertEquals([
            'first_name' => null,
            'last_name' => null,
            'display_name' => null,
            'work_email' => null,
        ], $viewModel->details());
    }

    #[Test]
    public function it_gives_the_company_name_and_the_email(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $viewModel = new ProfileViewModel(user: $user, employee: null);

        $this->assertEquals('Dunder Mifflin', $viewModel->companyName());
        $this->assertEquals('dwight.schrute@dundermifflin.com', $viewModel->email());
    }
}
