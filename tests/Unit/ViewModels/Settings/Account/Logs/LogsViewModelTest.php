<?php

declare(strict_types=1);

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\Log;
use App\Models\User;
use App\ViewModels\Settings\Account\Logs\LogsViewModel;

it('gives the logs of the user newest first', function () {
    $user = User::factory()->create();

    Log::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'action' => UserActionEnum::UserSignedIn->value,
        'created_at' => now()->subDays(3),
    ]);
    Log::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'action' => UserActionEnum::UserPasswordUpdated->value,
        'created_at' => now()->subDay(),
    ]);

    $viewModel = new LogsViewModel(user: $user);
    $logs = $viewModel->logs();

    expect($logs)->toHaveCount(2);
    expect($logs[0]->action)->toEqual(UserActionEnum::UserPasswordUpdated->value);
    expect($logs[1]->action)->toEqual(UserActionEnum::UserSignedIn->value);
});

it('leaves out the logs of somebody else', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $colleague = User::factory()->create(['company_id' => $company->id]);

    Log::factory()->create([
        'company_id' => $company->id,
        'user_id' => $colleague->id,
    ]);

    $viewModel = new LogsViewModel(user: $user);

    expect($viewModel->logs())->toHaveCount(0);
});

it('gives five logs a page', function () {
    $user = User::factory()->create();

    Log::factory()->count(7)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $viewModel = new LogsViewModel(user: $user);

    expect($viewModel->logs())->toHaveCount(5);
    expect($viewModel->logs()->hasMorePages())->toBeTrue();
});

it('gives the last five emails sent newest first', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(6)->sequence(fn ($sequence): array => [
        'subject' => 'Email '.$sequence->index,
        'sent_at' => now()->subDays(6 - $sequence->index),
    ])->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $viewModel = new LogsViewModel(user: $user);

    expect($viewModel->emailsSent())->toHaveCount(5);
    expect($viewModel->emailsSent()->first()->subject)->toEqual('Email 5');
    expect($viewModel->hasMoreEmailsSent())->toBeTrue();
});

it('says there is nothing more to read when there are five emails or fewer', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(5)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $viewModel = new LogsViewModel(user: $user);

    expect($viewModel->hasMoreEmailsSent())->toBeFalse();
});

it('hides the emails sent to somebody else', function () {
    $user = User::factory()->create();
    $colleague = User::factory()->create(['company_id' => $user->company_id]);
    EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $colleague->id,
    ]);

    $viewModel = new LogsViewModel(user: $user);

    expect($viewModel->emailsSent())->toHaveCount(0);
    expect($viewModel->hasMoreEmailsSent())->toBeFalse();
});
