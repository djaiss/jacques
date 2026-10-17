<?php

declare(strict_types=1);

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

it('opens the setup screen from the security screen', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings.security.index'));

    $page->click('Turn it on')
        ->assertPathIs('/settings/account/security/two-factor')
        ->assertSee('Pair your authenticator app')
        ->assertNoJavaScriptErrors();
});

it('turns two factor on with a valid code and shows recovery codes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('settings.twoFactor.new'));

    $secret = str_replace(' ', '', $page->text('p.font-mono'));

    $page->type('#code', new Google2FA()->getCurrentOtp($secret))
        ->press('form[action$="/security/two-factor"] button[type="submit"]')
        ->assertPathIs('/settings/account/security')
        ->assertSee('Two factor authentication is on.')
        ->assertSee('Recovery codes')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->two_factor_confirmed_at)->not->toBeNull();
});

it('refuses an invalid code and leaves two factor off', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('settings.twoFactor.new'));

    $page->type('#code', '000000')
        ->press('form[action$="/security/two-factor"] button[type="submit"]')
        ->assertSee('That code is not right.');

    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();

    visit(route('settings.security.index'))->assertSee('Turn it on');
});

it('gives new recovery codes once confirmed', function () {
    $user = User::factory()->twoFactor()->create();
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->assertSee('scranton-1')
        ->click('main button[type="button"]:has-text("Get new codes")')
        ->assertVisible('[role="dialog"] h2:has-text("Get new recovery codes?")')
        ->press('[role="dialog"] button:has-text("Get new codes")')
        ->assertSee('Your recovery codes are new.')
        ->assertDontSee('scranton-1')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->two_factor_recovery_codes)->not->toContain('scranton-1');
});

it('keeps two factor on when turning it off is cancelled', function () {
    $user = User::factory()->twoFactor()->create();
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->click('main button[type="button"]:has-text("Turn it off")')
        ->assertVisible('[role="dialog"] h2:has-text("Turn two factor off?")')
        ->press('[role="dialog"] button:has-text("Keep it on")')
        ->assertMissing('[role="dialog"] h2:has-text("Turn two factor off?")')
        ->assertSee('scranton-1')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->two_factor_confirmed_at)->not->toBeNull();
});

it('turns two factor off once confirmed', function () {
    $user = User::factory()->twoFactor()->create();
    $this->actingAs($user);

    $page = visit(route('settings.security.index'));

    $page->click('main button[type="button"]:has-text("Turn it off")')
        ->assertVisible('[role="dialog"] h2:has-text("Turn two factor off?")')
        ->press('[role="dialog"] button:has-text("Turn it off")')
        ->assertSee('Two factor authentication is off.')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();
});
