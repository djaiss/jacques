<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\EmailSent;
use App\Models\Staff;
use App\Models\User;

it('shows the emails sent screen', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Pam',
        'last_name' => 'Beesly',
        'user_id' => $user->id,
    ]);
    EmailSent::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'email_address' => 'pam.beesly@dundermifflin.com',
        'subject' => 'A sign-in from a new place',
    ]);

    $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

    $response->assertStatus(200);
    $response->assertSee('Every email we sent to your account, most recent first.', escape: false);
    $response->assertSee('Every email', escape: false);
    $response->assertSee('pam.beesly@dundermifflin.com', escape: false);
    $response->assertSee('A sign-in from a new place', escape: false);
    $response->assertSee('Pam Beesly', escape: false);
    $response->assertSee('Dunder Mifflin', escape: false);
});

it('shows the path from the dashboard down to the screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

    $response->assertStatus(200);
    $response->assertSee('aria-label="Breadcrumb"', escape: false);
    $response->assertSee('href="'.route('settings.logs.index').'"', escape: false);
    $response->assertSee('aria-current="page">Emails sent<', escape: false);
});

it('shows a blank state when nothing was ever sent', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

    $response->assertStatus(200);
    $response->assertSee('No emails yet', escape: false);
    $response->assertSee('Nothing has left our hands yet.', escape: false);
});

it('hides the emails sent to somebody else', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $colleague = User::factory()->create(['company_id' => $company->id]);
    EmailSent::factory()->create([
        'company_id' => $company->id,
        'user_id' => $colleague->id,
        'subject' => 'Your magic link',
    ]);

    $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Your magic link', escape: false);
});

it('offers to load more when a page is not enough', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(11)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

    $response->assertStatus(200);
    $response->assertSee('Load more', escape: false);
    $response->assertSee('id="pagination"', escape: false);
});

it('stops offering to load more on the last page', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(10)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Load more', escape: false);
});

it('redirects a visitor who is not signed in', function () {
    $response = $this->get(route('settings.emailsSent.index'));

    $response->assertRedirect(route('auth.signIn.new'));
});
