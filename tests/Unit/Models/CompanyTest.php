<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_many_users(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        User::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertTrue($company->users()->exists());
        $this->assertCount(2, $company->users()->get());
    }

    #[Test]
    public function it_has_many_locations(): void
    {
        $company = Company::factory()->create();
        Location::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertTrue($company->locations()->exists());
        $this->assertCount(2, $company->locations()->get());
    }

    #[Test]
    public function it_knows_whether_it_is_on_trial(): void
    {
        $onTrial = Company::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $expired = Company::factory()->create(['trial_ends_at' => now()->subDay()]);
        $never = Company::factory()->create(['trial_ends_at' => null]);

        $this->assertTrue($onTrial->isOnTrial());
        $this->assertFalse($expired->isOnTrial());
        $this->assertFalse($never->isOnTrial());
    }

    #[Test]
    public function it_does_not_owe_the_welcome_screen_by_default(): void
    {
        $company = Company::factory()->create();
        $pending = Company::factory()->welcomeScreenPending()->create();

        $this->assertFalse($company->refresh()->is_welcome_screen_pending);
        $this->assertTrue($pending->refresh()->is_welcome_screen_pending);
    }
}
