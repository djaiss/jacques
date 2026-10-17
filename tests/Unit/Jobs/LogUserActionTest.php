<?php

declare(strict_types=1);

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Log;
use App\Models\User;

it('logs the action of the user', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    new LogUserAction(
        company: $company,
        user: $user,
        action: UserActionEnum::CompanyUpdated,
        parameters: ['name' => 'Dunder Mifflin'],
    )->handle();

    $this->assertDatabaseHas('logs', [
        'company_id' => $company->id,
        'user_id' => $user->id,
        'user_email' => 'michael.scott@dundermifflin.com',
        'action' => UserActionEnum::CompanyUpdated->value,
    ]);

    expect(Log::query()->latest()->first()->parameters)->toEqual(['name' => 'Dunder Mifflin']);
});

it('keeps the email of the user once they are deleted', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    new LogUserAction(
        company: $company,
        user: $user,
        action: UserActionEnum::UserPasswordUpdated,
    )->handle();

    $user->forceDelete();

    $log = Log::query()->latest()->first();

    expect($log->user_id)->toBeNull();
    expect($log->user_email)->toEqual('michael.scott@dundermifflin.com');
});
