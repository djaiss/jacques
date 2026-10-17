<?php

declare(strict_types=1);

use App\Actions\DismissWelcomeScreen;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('dismisses the welcome screen of the company', function () {
    Queue::fake();

    $company = Company::factory()->welcomeScreenPending()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);

    $result = new DismissWelcomeScreen(user: $user)->execute();

    expect($result)->toBeInstanceOf(Company::class);
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
});

it('logs nothing when the welcome screen was already dismissed', function () {
    Queue::fake();

    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);

    new DismissWelcomeScreen(user: $user)->execute();

    expect($company->refresh()->is_welcome_screen_pending)->toBeFalse();
    Queue::assertNothingPushed();
});

it('leaves the other companies alone', function () {
    Queue::fake();

    $company = Company::factory()->welcomeScreenPending()->create(['name' => 'Dunder Mifflin']);
    $otherCompany = Company::factory()->welcomeScreenPending()->create(['name' => 'Staples']);
    $user = User::factory()->create(['company_id' => $company->id]);

    new DismissWelcomeScreen(user: $user)->execute();

    expect($otherCompany->refresh()->is_welcome_screen_pending)->toBeTrue();
});
