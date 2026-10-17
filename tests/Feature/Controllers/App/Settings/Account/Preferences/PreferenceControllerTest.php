<?php

declare(strict_types=1);

use App\Enums\TimeFormatEnum;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows the preferences screen', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'locale' => 'en',
        'time_format' => TimeFormatEnum::TwentyFourHour,
    ]);
    Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'display_name' => null,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('settings.preferences.index'));

    $response->assertStatus(200);
    $response->assertSee('Preferences', escape: false);
    $response->assertSee('Language', escape: false);
    $response->assertSee('Time format', escape: false);
    $response->assertSee('24-hour', escape: false);
    $response->assertSee('Français', escape: false);
    $response->assertSee('How the application reads to you, on every device you sign in from.', escape: false);
    $response->assertSee('The language the interface is drawn in.', escape: false);
    $response->assertSee('How every clock time is written. Dates are unaffected.', escape: false);
    $response->assertSee('Michael Scott', escape: false);
    $response->assertSee('Dunder Mifflin', escape: false);
});

it('writes the email address in the top bar when there is no staff record', function () {
    $user = User::factory()->create([
        'email' => 'accountant@vancerefrigeration.com',
    ]);

    $response = $this->actingAs($user)->get(route('settings.preferences.index'));

    $response->assertStatus(200);
    $response->assertSee('accountant@vancerefrigeration.com', escape: false);
});

it('refuses a visitor who is not signed in', function () {
    $response = $this->get(route('settings.preferences.index'));

    $response->assertRedirect(route('auth.signIn.new'));
});

it('saves the preferences', function () {
    Queue::fake();

    $user = User::factory()->create([
        'locale' => 'en',
        'time_format' => TimeFormatEnum::TwentyFourHour,
    ]);

    $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
        'locale' => 'fr_FR',
        'time_format' => '12',
    ]);

    $response->assertRedirect(route('settings.preferences.index'));

    $response->assertSessionHas('status', 'Vos préférences sont enregistrées.');

    $user->refresh();

    expect($user->locale)->toEqual('fr_FR');
    expect($user->time_format)->toEqual(TimeFormatEnum::TwelveHour);
});

it('puts the new language in the session so the screen comes back in it', function () {
    Queue::fake();

    $user = User::factory()->create(['locale' => 'en']);

    $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
        'locale' => 'de_DE',
        'time_format' => '24',
    ]);

    $response->assertSessionHas('locale', 'de_DE');
});

it('refuses a language we do not speak', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
        'locale' => 'kl_GL',
        'time_format' => '24',
    ]);

    $response->assertSessionHasErrors('locale');
    expect($user->refresh()->locale)->toEqual('en');
});

it('refuses a time format that does not exist', function () {
    $user = User::factory()->create(['time_format' => TimeFormatEnum::TwentyFourHour]);

    $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
        'locale' => 'en',
        'time_format' => '36',
    ]);

    $response->assertSessionHasErrors('time_format');
    expect($user->refresh()->time_format)->toEqual(TimeFormatEnum::TwentyFourHour);
});
