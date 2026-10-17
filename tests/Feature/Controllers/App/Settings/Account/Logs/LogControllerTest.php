<?php

declare(strict_types=1);

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\Log;
use App\Models\Staff;
use App\Models\User;

it('shows the logs screen', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'display_name' => null,
        'user_id' => $user->id,
    ]);

    Log::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'action' => UserActionEnum::CompanyUpdated->value,
        'parameters' => ['name' => 'Dunder Mifflin'],
    ]);

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertSee('What we recorded about your account, and what we sent you.', escape: false);
    $response->assertSee('Activity', escape: false);
    $response->assertSee('Michael Scott', escape: false);
    $response->assertSee('company_updated', escape: false);
    $response->assertSee('Updated the company called Dunder Mifflin', escape: false);
});

it('shows a blank state when there is nothing to read yet', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertSee('No activity yet', escape: false);
    $response->assertSee('Nothing has happened on your account yet.', escape: false);
    $response->assertSee('No emails yet', escape: false);
    $response->assertSee('Nothing has left our hands yet.', escape: false);
});

it('hides the logs of somebody else', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $colleague = User::factory()->create(['company_id' => $company->id]);

    Log::factory()->create([
        'company_id' => $company->id,
        'user_id' => $colleague->id,
        'action' => UserActionEnum::CompanyUpdated->value,
        'parameters' => ['name' => 'Vance Refrigeration'],
    ]);

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Updated the company called Vance Refrigeration', escape: false);
});

it('offers to load more when a page is not enough', function () {
    $user = User::factory()->create();

    Log::factory()->count(11)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertSee('Load more', escape: false);
    $response->assertSee('id="pagination"', escape: false);
});

it('stops offering to load more on the last page', function () {
    $user = User::factory()->create();

    Log::factory()->count(3)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Load more', escape: false);
});

it('shows the latest emails sent and offers to browse the rest', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(7)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'sent_at' => now()->subWeek(),
    ]);
    EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'email_address' => 'creed.bratton@dundermifflin.com',
        'subject' => 'Confirm your email address',
        'sent_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertSee('creed.bratton@dundermifflin.com', escape: false);
    $response->assertSee('Confirm your email address', escape: false);
    $response->assertSee('Browse all emails', escape: false);
    $response->assertSee(route('settings.emailsSent.index'), escape: false);
});

it('hides the link to the emails when there is nothing more to read', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(5)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Browse all emails', escape: false);
});

it('hides the emails sent to somebody else', function () {
    $user = User::factory()->create();
    $colleague = User::factory()->create(['company_id' => $user->company_id]);
    EmailSent::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $colleague->id,
        'subject' => 'Your beet order is on its way',
    ]);

    $response = $this->actingAs($user)->get(route('settings.logs.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Your beet order is on its way', escape: false);
});

it('redirects a visitor who is not signed in', function () {
    $response = $this->get(route('settings.logs.index'));

    $response->assertRedirect(route('auth.signIn.new'));
});
