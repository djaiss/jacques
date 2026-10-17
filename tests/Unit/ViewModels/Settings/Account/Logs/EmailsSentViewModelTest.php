<?php

declare(strict_types=1);

use App\Models\EmailSent;
use App\Models\User;
use App\ViewModels\Settings\Account\Logs\EmailsSentViewModel;

it('gives the emails sent newest first', function () {
    $user = User::factory()->create();
    EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'subject' => 'Confirm your email address',
        'sent_at' => now()->subDays(3),
    ]);
    EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'subject' => 'A sign-in from a new place',
        'sent_at' => now()->subDay(),
    ]);

    $viewModel = new EmailsSentViewModel(user: $user);

    expect($viewModel->emailsSent())->toHaveCount(2);
    expect($viewModel->emailsSent()->first()->subject)->toEqual('A sign-in from a new place');
});

it('hides the emails sent to somebody else', function () {
    $user = User::factory()->create();
    $colleague = User::factory()->create(['company_id' => $user->company_id]);
    EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $colleague->id,
    ]);

    $viewModel = new EmailsSentViewModel(user: $user);

    expect($viewModel->emailsSent())->toHaveCount(0);
});

it('gives ten emails a page', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(11)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $viewModel = new EmailsSentViewModel(user: $user);

    expect($viewModel->emailsSent())->toHaveCount(10);
    expect($viewModel->emailsSent()->hasMorePages())->toBeTrue();
});
