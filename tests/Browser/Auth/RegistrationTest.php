<?php

declare(strict_types=1);

use App\Models\User;

it('creates an account and asks to confirm the email address', function () {
    $page = visit(route('auth.register.new'));

    $page->assertSee('Create your account')
        ->type('first_name', 'Michael')
        ->type('last_name', 'Scott')
        ->type('company_name', 'Dunder Mifflin')
        ->type('email', 'michael.scott@dundermifflin.com')
        ->type('password', 'thatswhatshesaid')
        ->type('password_confirmation', 'thatswhatshesaid')
        ->click('label[for="terms"] span[aria-hidden]')
        ->press('text="Next step: validate your email address"')
        ->assertPathIs('/verify-email')
        ->assertSee('Confirm your email address')
        ->assertSee('michael.scott@dundermifflin.com')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'michael.scott@dundermifflin.com')->exists())->toBeTrue();
});

it('shows the errors when the passwords do not match', function () {
    $page = visit(route('auth.register.new'));

    $page->type('first_name', 'Dwight')
        ->type('last_name', 'Schrute')
        ->type('company_name', 'Schrute Farms')
        ->type('email', 'dwight.schrute@schrutefarms.com')
        ->type('password', 'bearsbeetsbattlestar')
        ->type('password_confirmation', 'identitytheftisnotajoke')
        ->click('label[for="terms"] span[aria-hidden]')
        ->press('text="Next step: validate your email address"')
        ->assertPathIs('/register')
        ->assertSee('The password field confirmation does not match.')
        ->assertValue('email', 'dwight.schrute@schrutefarms.com');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

it('leads to the sign in page for somebody who already has an account', function () {
    $page = visit(route('auth.register.new'));

    $page->click('Sign in instead')
        ->assertPathIs('/sign-in')
        ->assertSee('Welcome back');
});
