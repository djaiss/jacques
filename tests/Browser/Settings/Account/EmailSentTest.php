<?php

declare(strict_types=1);

use App\Models\EmailSent;
use App\Models\User;

it('shows that no email was sent to an account', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings.emailsSent.index'));

    $page->assertSee('No emails yet')
        ->assertNoJavaScriptErrors();
});

it('shows ten emails and loads the eleventh on request', function () {
    $user = User::factory()->create();

    foreach (range(1, 11) as $number) {
        EmailSent::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'subject' => "Dunder Mifflin memo {$number}",
            'sent_at' => now()->subHours($number),
        ]);
    }

    $this->actingAs($user);

    $page = visit(route('settings.emailsSent.index'));

    $page->assertSee('Dunder Mifflin memo 2')
        ->assertSee('Dunder Mifflin memo 10')
        ->assertDontSee('Dunder Mifflin memo 11')
        ->click('a:has-text("Load more")')
        ->assertSee('Dunder Mifflin memo 11')
        ->assertDontSee('Load more')
        ->assertNoJavaScriptErrors();
});

it('opens the emails sent screen from the logs screen', function () {
    $user = User::factory()->create();
    EmailSent::factory()->count(6)->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $page = visit(route('settings.logs.index'));

    $page->click('a:has-text("Browse all emails")')
        ->assertPathIs('/settings/account/logs/emails')
        ->assertSee('Every email')
        ->assertNoJavaScriptErrors();
});
