<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DismissWelcomeScreen;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DismissWelcomeScreenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_dismisses_the_welcome_screen_of_the_company(): void
    {
        Queue::fake();

        $company = Company::factory()->welcomeScreenPending()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $result = new DismissWelcomeScreen(user: $user)->execute();

        $this->assertInstanceOf(Company::class, $result);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_welcome_screen_pending' => false,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::WelcomeScreenDismissed
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_logs_nothing_when_the_welcome_screen_was_already_dismissed(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        new DismissWelcomeScreen(user: $user)->execute();

        $this->assertFalse($company->refresh()->is_welcome_screen_pending);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_leaves_the_other_companies_alone(): void
    {
        Queue::fake();

        $company = Company::factory()->welcomeScreenPending()->create(['name' => 'Dunder Mifflin']);
        $otherCompany = Company::factory()->welcomeScreenPending()->create(['name' => 'Staples']);
        $user = User::factory()->create(['company_id' => $company->id]);

        new DismissWelcomeScreen(user: $user)->execute();

        $this->assertTrue($otherCompany->refresh()->is_welcome_screen_pending);
    }
}
