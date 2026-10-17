<?php

declare(strict_types=1);

use App\Models\Log;
use App\Models\User;

it('shows that nothing happened on an account without activity', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings.logs.index'));

    $page->assertSee('No activity yet')
        ->assertSee('No emails yet')
        ->assertNoJavaScriptErrors();
});

it('shows five entries and loads the sixth on request', function () {
    $user = User::factory()->create();

    $branches = ['Scranton', 'Stamford', 'Utica', 'Nashua', 'Albany', 'Akron'];

    foreach ($branches as $index => $branch) {
        Log::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'parameters' => ['name' => "Dunder Mifflin {$branch}"],
            'created_at' => now()->subHours($index + 1),
        ]);
    }

    $this->actingAs($user);

    $page = visit(route('settings.logs.index'));

    $page->assertSee('Dunder Mifflin Scranton')
        ->assertSee('Dunder Mifflin Albany')
        ->assertDontSee('Dunder Mifflin Akron')
        ->click('a:has-text("Load more")')
        ->assertSee('Dunder Mifflin Akron')
        ->assertDontSee('Load more')
        ->assertNoJavaScriptErrors();
});

it('never shows the activity of somebody else', function () {
    $user = User::factory()->create();
    $colleague = User::factory()->create(['company_id' => $user->company_id]);
    Log::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'parameters' => ['name' => 'Dunder Mifflin Scranton'],
    ]);
    Log::factory()->create([
        'company_id' => $user->company_id,
        'user_id' => $colleague->id,
        'parameters' => ['name' => 'Vance Refrigeration'],
    ]);

    $this->actingAs($user);

    $page = visit(route('settings.logs.index'));

    $page->assertSee('Dunder Mifflin Scranton')
        ->assertDontSee('Vance Refrigeration')
        ->assertNoJavaScriptErrors();
});
