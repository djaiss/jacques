<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\ViewModels\Settings\SettingsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_setting_of_the_account(): void
    {
        $user = User::factory()->create();

        $viewModel = new SettingsViewModel(user: $user, employee: null);

        $titles = array_column($viewModel->accountRows(), 'title');

        $this->assertSame(['Profile', 'Security and access', 'Preferences', 'Logs'], $titles);
    }

    #[Test]
    public function it_says_whether_two_factor_authentication_is_on(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);

        $viewModel = new SettingsViewModel(user: $user, employee: null);

        $this->assertSame('Two-factor on', $viewModel->accountRows()[1]['value']);
    }

    #[Test]
    public function it_says_when_two_factor_authentication_is_off(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);

        $viewModel = new SettingsViewModel(user: $user, employee: null);

        $this->assertSame('Two-factor off', $viewModel->accountRows()[1]['value']);
    }

    #[Test]
    public function it_counts_the_locations_of_the_company(): void
    {
        $company = Company::factory()->create();
        $user = $this->grant(
            User::factory()->create(['company_id' => $company->id]),
            PermissionEnum::CompanyManage,
        );
        Location::factory()->count(2)->create(['company_id' => $company->id]);

        $viewModel = new SettingsViewModel(user: $user, employee: null);

        $this->assertSame('2 locations', $viewModel->companyRows()[0]['value']);
    }

    #[Test]
    public function it_offers_nothing_about_the_company_to_somebody_who_administers_none_of_it(): void
    {
        $user = User::factory()->create();

        $viewModel = new SettingsViewModel(user: $user, employee: null);

        $this->assertSame([], $viewModel->companyRows());
    }

    #[Test]
    public function it_falls_back_to_the_email_address_when_nobody_works_here(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.example']);

        $viewModel = new SettingsViewModel(user: $user, employee: null);

        $this->assertSame('michael.scott@dundermifflin.example', $viewModel->name());
    }

    #[Test]
    public function it_names_the_role_of_somebody(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = $this->grant(
            User::factory()->create(['company_id' => $company->id, 'employee_id' => $employee->id]),
            PermissionEnum::CompanyManage,
        );

        $viewModel = new SettingsViewModel(user: $user, employee: $employee);

        $this->assertSame('company.manage at company scope', $viewModel->role());
    }
}
