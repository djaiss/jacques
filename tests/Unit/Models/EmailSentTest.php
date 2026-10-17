<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\EmailSent;
use App\Models\User;

it('belongs to a company', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $emailSent = EmailSent::factory()->create(['company_id' => $company->id]);

    expect($emailSent->company()->exists())->toBeTrue();
    expect($emailSent->company->name)->toEqual('Dunder Mifflin');
});

it('belongs to a user', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    $emailSent = EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    expect($emailSent->user()->exists())->toBeTrue();
    expect($emailSent->user->email)->toEqual('michael.scott@dundermifflin.com');
});

it('has no user when the system emailed someone without an account', function () {
    $emailSent = EmailSent::factory()->withoutUser()->create();

    expect($emailSent->user()->exists())->toBeFalse();
    expect($emailSent->user)->toBeNull();
});
