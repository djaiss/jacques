<?php

declare(strict_types=1);

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\Log;
use App\Models\Staff;
use App\Models\User;

it('belongs to a company', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $log = Log::factory()->create(['company_id' => $company->id]);

    expect($log->company()->exists())->toBeTrue();
    expect($log->company->name)->toEqual('Dunder Mifflin');
});

it('belongs to a user', function () {
    $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    $log = Log::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    expect($log->user()->exists())->toBeTrue();
    expect($log->user->email)->toEqual('michael.scott@dundermifflin.com');
});

it('casts the parameters to an array', function () {
    $log = Log::factory()->create(['parameters' => ['name' => 'Dunder Mifflin']]);

    expect($log->refresh()->parameters)->toEqual(['name' => 'Dunder Mifflin']);
});

it('gives the name of whoever performed the action', function () {
    $user = User::factory()->create();
    Staff::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'display_name' => null,
    ]);
    $log = Log::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    expect($log->author)->toEqual('Michael Scott');
});

it('falls back to the recorded email when the user is gone', function () {
    $log = Log::factory()->create([
        'user_id' => null,
        'user_email' => 'michael.scott@dundermifflin.com',
    ]);

    expect($log->author)->toEqual('michael.scott@dundermifflin.com');
});

it('describes the action with its parameters', function () {
    $log = Log::factory()->create([
        'action' => UserActionEnum::CompanyUpdated->value,
        'parameters' => ['name' => 'Dunder Mifflin'],
    ]);

    expect($log->description)->toEqual('Updated the company called Dunder Mifflin');
});

it('describes an action that carries no parameter', function () {
    $log = Log::factory()->create([
        'action' => UserActionEnum::UserSignedIn->value,
        'parameters' => null,
    ]);

    expect($log->description)->toEqual('Signed in');
});

it('falls back to the raw name of an action it does not know', function () {
    $log = Log::factory()->create([
        'action' => 'stapler_put_in_jelly',
        'parameters' => null,
    ]);

    expect($log->description)->toEqual('stapler_put_in_jelly');
});
