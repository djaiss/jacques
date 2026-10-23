<?php

declare(strict_types=1);

use App\Models\User;

it('shows the month asked for and keeps the selected day', function () {
    $michael = User::factory()->create();

    $response = $this->actingAs($michael)->get(route('dashboard.calendar.show', ['date' => '2026-10-26', 'month' => '2026-12']));

    $response->assertOk();
    $response->assertSee('December 2026');
    $response->assertSee('Monday 26 October 2026');
    $response->assertSee('href="'.route('dashboard.calendar.show', ['date' => '2026-10-26', 'month' => '2027-01']).'"', escape: false);
});

it('does not find a month that does not exist', function () {
    $michael = User::factory()->create();

    $this->actingAs($michael)->get('/dashboard/2026-10-26/calendar/2026-13')->assertNotFound();
});

it('sends a guest to sign in', function () {
    $this->get(route('dashboard.calendar.show', ['date' => '2026-10-26', 'month' => '2026-12']))->assertRedirect(route('auth.signIn.new'));
});
