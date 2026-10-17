<?php

declare(strict_types=1);

use App\Actions\UpdatePreferences;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('updates the preferences of a user', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'locale' => 'en',
        'time_format' => TimeFormatEnum::TwentyFourHour,
    ]);

    $result = new UpdatePreferences(
        user: $user,
        locale: 'fr_FR',
        timeFormat: TimeFormatEnum::TwelveHour,
    )->execute();

    expect($result)->toBeInstanceOf(User::class);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'locale' => 'fr_FR',
        'time_format' => '12',
    ]);
});

it('logs the change', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    new UpdatePreferences(
        user: $user,
        locale: 'de_DE',
        timeFormat: TimeFormatEnum::TwelveHour,
    )->execute();

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPreferencesUpdated
            && $job->company->id === $company->id
            && $job->user->id === $user->id,
    );
});
