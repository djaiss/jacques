<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('changes the password when the current one is right', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->type('#current_password', 'password')
        ->type('#new_password', 'Threat level midnight')
        ->type('#new_password_confirmation', 'Threat level midnight')
        ->press('form[action$="/security/password"] button[type="submit"]')
        ->assertSee('Your password is changed.')
        ->assertNoJavaScriptErrors();

    expect(Hash::check('Threat level midnight', $user->refresh()->password_hash))->toBeTrue();
});

it('refuses a wrong current password', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->type('#current_password', 'dunder-mifflin')
        ->type('#new_password', 'Threat level midnight')
        ->type('#new_password_confirmation', 'Threat level midnight')
        ->press('form[action$="/security/password"] button[type="submit"]')
        ->assertDontSee('Your password is changed.')
        ->assertSee('The password is incorrect.');

    expect(Hash::check('password', $user->refresh()->password_hash))->toBeTrue();
});

it('changes the password on a phone', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('settings.security.index'))->on()->mobile();

    $page->type('#current_password', 'password')
        ->type('#new_password', 'Threat level midnight')
        ->type('#new_password_confirmation', 'Threat level midnight')
        ->press('form[action$="/security/password"] button[type="submit"]')
        ->assertSee('Your password is changed.')
        ->assertNoJavaScriptErrors();

    expect(Hash::check('Threat level midnight', $user->refresh()->password_hash))->toBeTrue();
});

it('shows an empty list when there is no api key', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings.security.index'));

    $page->assertSee('No API keys yet')
        ->assertNoJavaScriptErrors();
});

it('creates an api key and shows the token once', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->click('Create key')
        ->type('#name', 'Dundie awards bot')
        ->press('form[action$="/security/api-keys"] button[type="submit"]')
        ->assertSee('Your new API key')
        ->assertSee('Dundie awards bot')
        ->assertDontSee('No API keys yet')
        ->assertNoJavaScriptErrors();

    expect($user->tokens()->where('name', 'Dundie awards bot')->exists())->toBeTrue();

    $page = visit(route('settings.security.index'));

    $page->assertSee('Dundie awards bot')
        ->assertDontSee('Your new API key');
});

it('keeps an api key when the revoke is cancelled', function () {
    $user = User::factory()->create();
    $user->createToken('Dundie awards bot');
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->click('main button[type="button"]:has-text("Revoke")')
        ->assertVisible('[role="dialog"] h2:has-text("Revoke Dundie awards bot?")')
        ->press('[role="dialog"] button:has-text("Keep it")')
        ->assertMissing('[role="dialog"] h2:has-text("Revoke Dundie awards bot?")')
        ->assertSee('Dundie awards bot')
        ->assertNoJavaScriptErrors();

    expect($user->tokens()->count())->toBe(1);
});

it('revokes an api key once confirmed', function () {
    $user = User::factory()->create();
    $user->createToken('Dundie awards bot');
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->click('main button[type="button"]:has-text("Revoke")')
        ->assertVisible('[role="dialog"] h2:has-text("Revoke Dundie awards bot?")')
        ->press('[role="dialog"] button:has-text("Revoke it")')
        ->assertSee('The API key is revoked.')
        ->assertSee('No API keys yet')
        ->assertNoJavaScriptErrors();

    expect($user->tokens()->count())->toBe(0);
});
