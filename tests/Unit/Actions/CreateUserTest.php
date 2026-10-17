<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a user in the company', function () {
    $company = Company::factory()->create();

    $user = new CreateUser(
        company: $company,
        email: 'jim.halpert@dundermifflin.com',
        password: 'bearsbeatsbattlestar',
    )->execute();

    expect($user)->toBeInstanceOf(User::class);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'company_id' => $company->id,
        'email' => 'jim.halpert@dundermifflin.com',
        'is_active' => true,
    ]);
    expect(Hash::check('bearsbeatsbattlestar', $user->password_hash))->toBeTrue();

    expect($user->password_changed_at?->timestamp)->toEqualWithDelta(now()->timestamp, 2);
});

it('creates a user without a password when they sign in through sso', function () {
    $company = Company::factory()->create();

    $user = new CreateUser(
        company: $company,
        email: 'pam.beesly@dundermifflin.com',
        ssoProvider: 'google',
    )->execute();

    expect($user->password_hash)->toBeNull();
    expect($user->password_changed_at)->toBeNull();
    expect($user->sso_provider)->toEqual('google');
    expect($user->usesSingleSignOn())->toBeTrue();
});
