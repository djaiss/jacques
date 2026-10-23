<?php

declare(strict_types=1);

use App\Models\User;

it('moves to the next day and pages the calendar without leaving it', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('dashboard.show', ['date' => '2026-10-26']));

    $page->assertSee('Monday 26 October 2026')
        ->click('a[aria-label="Next day"]')
        ->assertPathIs('/dashboard/2026-10-27')
        ->assertSee('Tuesday 27 October 2026')
        ->click('a[aria-label="Next month"]')
        ->assertSeeIn('#calendar', 'November 2026')
        ->assertPathIs('/dashboard/2026-10-27')
        ->assertSee('Tuesday 27 October 2026')
        ->assertNoJavaScriptErrors();
});

it('shows only the hairdressers that are checked', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('dashboard.show', ['date' => '2026-10-26']));

    $page->assertSee('32 appointments in total')
        ->click('label[for="hairdresser-all"]')
        ->assertSee('40 appointments in total')
        ->click('label[for="hairdresser-all"]')
        ->assertSee('No hairdresser is shown')
        ->click('label[for="hairdresser-sammy"]')
        ->assertSee('7 appointments in total')
        ->assertDontSee('No hairdresser is shown')
        ->assertNoJavaScriptErrors();
});

it('asks before cancelling an appointment', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('dashboard.show', ['date' => '2026-10-26']));

    $page->click('button:has-text("Hugo Moreau")')
        ->assertSee('Cancel this appointment')
        ->click('Cancel this appointment')
        ->assertSee('The appointment leaves the agenda and the slot becomes free again. Are you sure?')
        ->click('Keep it')
        ->assertSee('32 appointments in total')
        ->click('Cancel this appointment')
        ->click('Cancel it')
        ->assertSee('31 appointments in total')
        ->assertNoJavaScriptErrors();
});

it('books a free slot until the page reloads', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('dashboard.show', ['date' => '2026-10-26']));

    $page->click('button[aria-label^="Book at 10:00"][aria-label$="with Amélie"]')
        ->assertSee('New appointment')
        ->assertAttribute('button:has-text("Add the appointment")', 'disabled', '')
        ->type('#appointment-client', 'Pam Beesly')
        ->click('Add the appointment')
        ->assertSee('Pam Beesly')
        ->assertSee('33 appointments in total')
        ->assertNoJavaScriptErrors();

    visit(route('dashboard.show', ['date' => '2026-10-26']))
        ->assertDontSee('Pam Beesly')
        ->assertSee('32 appointments in total');
});
