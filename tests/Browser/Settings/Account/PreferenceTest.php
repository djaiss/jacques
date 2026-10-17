<?php

declare(strict_types=1);

use App\Enums\TimeFormatEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;

it('draws the screen in the language that was picked', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id, 'locale' => 'en']);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);
    $this->actingAs($user);

    $page = visit(route('settings.preferences.index'));

    $page->assertSee('How the application reads to you')
        ->click('button[aria-labelledby="locale-label locale-value"]')
        ->assertSee('Interface language')
        ->click('#language-form button[value="fr_FR"]')
        ->assertSee('Préférences')
        ->assertDontSee('How the application reads to you')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'locale' => 'fr_FR']);
});

it('changes the time format and keeps it after a reload', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'time_format' => TimeFormatEnum::TwentyFourHour,
    ]);
    Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $this->giveDefaultRole($user, Role::STAFF);
    $this->actingAs($user);

    $page = visit(route('settings.preferences.index'));

    $page->assertSee('It is')
        ->click('button[aria-labelledby="time-format-label time_format-value"]')
        ->assertSee('Time format')
        ->click('#time-format-form button[value="12"]')
        ->waitForText('12-hour')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'time_format' => '12']);

    $page->refresh()->assertSeeIn('#time_format-value', '12-hour');
    expect($page->text('#time-preview'))->toMatch('/\d{1,2}:\d{2} (AM|PM)/');
});
