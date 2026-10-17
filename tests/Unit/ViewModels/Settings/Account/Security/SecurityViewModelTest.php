<?php

declare(strict_types=1);

use App\Models\User;
use App\ViewModels\Settings\Account\Security\SecurityViewModel;

it('gives how long ago the password was changed', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(2)]);

    $viewModel = new SecurityViewModel(user: $user);

    expect($viewModel->passwordChangedAt())->toEqual('2 days ago');
});

it('gives nothing when the password was never changed', function () {
    $user = User::factory()->create(['password_changed_at' => null]);

    $viewModel = new SecurityViewModel(user: $user);

    expect($viewModel->passwordChangedAt())->toBeNull();
});

it('says whether the account signs in through a provider', function () {
    $user = User::factory()->create();
    $viewModel = new SecurityViewModel(user: $user);

    expect($viewModel->usesSingleSignOn())->toBeFalse();

    $singleSignOnUser = User::factory()->singleSignOn()->create();
    $singleSignOnViewModel = new SecurityViewModel(user: $singleSignOnUser);

    expect($singleSignOnViewModel->usesSingleSignOn())->toBeTrue();
});

it('gives an empty list when no api key has been made', function () {
    $user = User::factory()->create();

    $viewModel = new SecurityViewModel(user: $user);

    expect($viewModel->apiKeys())->toBe([]);
    expect($viewModel->apiKeysHeader())->toEqual('Active · no API keys');
});

it('gives the api keys newest first', function () {
    $user = User::factory()->create();

    $older = $user->createToken('Beet farm sync')->accessToken;
    $older->forceFill(['created_at' => now()->subDays(3)])->save();

    $user->createToken('Dundie awards bot');

    $apiKeys = new SecurityViewModel(user: $user)->apiKeys();

    expect($apiKeys)->toHaveCount(2);
    expect($apiKeys[0]['name'])->toEqual('Dundie awards bot');
    expect($apiKeys[1]['name'])->toEqual('Beet farm sync');
    expect($apiKeys[1]['createdAt'])->toEqual('3 days ago');
});

it('gives nothing for the last use until something uses the key', function () {
    $user = User::factory()->create();
    $user->createToken('Dundie awards bot');

    $apiKeys = new SecurityViewModel(user: $user)->apiKeys();

    expect($apiKeys[0]['lastUsedAt'])->toBeNull();
});

it('gives how long ago the api key was last used', function () {
    $user = User::factory()->create();
    $apiKey = $user->createToken('Dundie awards bot')->accessToken;
    $apiKey->forceFill(['last_used_at' => now()->subHours(5)])->save();

    $apiKeys = new SecurityViewModel(user: $user)->apiKeys();

    expect($apiKeys[0]['lastUsedAt'])->toEqual('5 hours ago');
});

it('counts the api keys in the header', function () {
    $user = User::factory()->create();
    $user->createToken('Dundie awards bot');

    expect(new SecurityViewModel(user: $user)->apiKeysHeader())->toEqual('Active · 1 API key');

    $user->createToken('Beet farm sync');

    expect(new SecurityViewModel(user: $user)->apiKeysHeader())->toEqual('Active · 2 API keys');
});
