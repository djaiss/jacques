<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Profile;

use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Settings\Account\Profile\ProfileViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_details_of_the_staff_member(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
            'display_name' => null,
            'email' => 'stanley.hudson@dundermifflin.com',
            'user_id' => $user->id,
        ]);

        $viewModel = new ProfileViewModel(user: $user, staff: $staff);

        $this->assertEquals([
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
            'display_name' => null,
            'work_email' => 'stanley.hudson@dundermifflin.com',
        ], $viewModel->details());
    }

    #[Test]
    public function it_gives_the_name_the_staff_member_goes_by(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
            'display_name' => 'Big Tuna',
            'user_id' => $user->id,
        ]);

        $viewModel = new ProfileViewModel(user: $user, staff: $staff);

        $this->assertEquals('Big Tuna', $viewModel->name());
    }

    #[Test]
    public function it_falls_back_to_the_email_when_there_is_no_staff_record(): void
    {
        $user = User::factory()->create([
            'email' => 'accountant@vancerefrigeration.com',
        ]);

        $viewModel = new ProfileViewModel(user: $user, staff: null);

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

        $viewModel = new ProfileViewModel(user: $user, staff: null);

        $this->assertEquals('Dunder Mifflin', $viewModel->companyName());
        $this->assertEquals('dwight.schrute@dundermifflin.com', $viewModel->email());
    }
}
