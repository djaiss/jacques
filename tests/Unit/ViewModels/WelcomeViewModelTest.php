<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels;

use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\WelcomeViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WelcomeViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_first_name_of_the_person(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Staff::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'user_id' => $user->id,
        ]);

        $viewModel = new WelcomeViewModel(user: $user);

        $this->assertEquals('Pam', $viewModel->firstName());
    }

    #[Test]
    public function it_falls_back_to_the_email_address_without_a_staff_record(): void
    {
        $user = User::factory()->create([
            'email' => 'pam.beesly@dundermifflin.com',
        ]);

        $viewModel = new WelcomeViewModel(user: $user);

        $this->assertEquals('pam.beesly@dundermifflin.com', $viewModel->firstName());
    }
}
