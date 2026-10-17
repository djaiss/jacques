<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;

it('signs a user in', function () {
    $user = User::factory()
        ->for(Company::factory()->welcomeScreenPending())
        ->create(['email' => 'pam.beesly@dundermifflin.com']);

    $page = visit(route('auth.signIn.new'));

    $page->assertSee('Welcome back')
        ->type('email', 'pam.beesly@dundermifflin.com')
        ->type('password', 'password')
        ->press('Continue')
        ->assertPathIs('/welcome')
        ->assertSee('Welcome to Jacques')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});

it('refuses a wrong password', function () {
    User::factory()->create(['email' => 'pam.beesly@dundermifflin.com']);

    $page = visit(route('auth.signIn.new'));

    $page->type('email', 'pam.beesly@dundermifflin.com')
        ->type('password', 'jimhalpert')
        ->press('Continue')
        ->assertPathIs('/sign-in')
        ->assertSee('These credentials do not match our records.')
        ->assertValue('email', 'pam.beesly@dundermifflin.com');

    $this->assertGuest();
});

it('leads to the registration page for somebody new', function () {
    $page = visit(route('auth.signIn.new'));

    $page->click('Create a company')
        ->assertPathIs('/register')
        ->assertSee('Create your account');
});
