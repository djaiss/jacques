<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\WelcomeControllerTest;

use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows the welcome screen to a new company', function () {
    $user = newcomer();

    $response = $this->actingAs($user)->get(route('welcome.index'));

    $response->assertStatus(200);
    $response->assertSee('Hi Michael, welcome to Jacques.');
    $response->assertSee('Creator of Jacques');
    $response->assertSee(route('welcome.update'));
    $response->assertSee('data-turbo="true"', false);
});

it('sends a company already welcomed to home', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('welcome.index'));

    $response->assertRedirect(route('home.index'));
});

it('dismisses the welcome screen', function () {
    Queue::fake();

    $user = newcomer();

    $response = $this->actingAs($user)->put(route('welcome.update'));

    $response->assertRedirect(route('onboarding.index'));
    expect($user->company->refresh()->is_welcome_screen_pending)->toBeFalse();
});

it('does not show the welcome screen again once dismissed', function () {
    Queue::fake();

    $user = newcomer();

    $this->actingAs($user)->put(route('welcome.update'));

    $this->actingAs($user)->get(route('welcome.index'))->assertRedirect(route('home.index'));
    $this->actingAs($user)->get(route('home.index'))->assertStatus(200);
});

it('sends a new company to the welcome screen from any signed in screen', function () {
    $user = newcomer();

    $this->actingAs($user)->get(route('home.index'))->assertRedirect(route('welcome.index'));
    $this->actingAs($user)->get(route('settings.index'))->assertRedirect(route('welcome.index'));
    $this->actingAs($user)->get(route('settings.profile.index'))->assertRedirect(route('welcome.index'));
    $this->actingAs($user)->get(route('settings.roles.index'))->assertRedirect(route('welcome.index'));
});

it('lets a new company see the email confirmation notice', function () {
    $user = newcomer(['email_verified_at' => null]);

    $response = $this->actingAs($user)->get(route('auth.verification.notice'));

    $response->assertStatus(200);
});

it('lets a guest reach home', function () {
    $response = $this->get(route('home.index'));

    $response->assertStatus(200);
});

it('sends a guest to sign in', function () {
    $this->get(route('welcome.index'))->assertRedirect(route('auth.signIn.new'));
    $this->put(route('welcome.update'))->assertRedirect(route('auth.signIn.new'));
});

/** @param  array<string, mixed>  $attributes */
function newcomer(array $attributes = []): User
{
    $company = Company::factory()->welcomeScreenPending()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        ...$attributes,
    ]);

    Staff::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
    ]);

    return $user;
}
