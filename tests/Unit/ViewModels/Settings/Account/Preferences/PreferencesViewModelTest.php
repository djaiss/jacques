<?php

declare(strict_types=1);

use App\Enums\TimeFormatEnum;
use App\Models\User;
use App\ViewModels\Settings\Account\Preferences\PreferencesViewModel;
use Carbon\Carbon;

it('lists every language and ticks the one in use', function () {
    app()->setLocale('fr_FR');

    $user = User::factory()->create(['locale' => 'fr_FR']);

    $viewModel = new PreferencesViewModel(user: $user);

    $locales = $viewModel->locales();

    expect($locales)->toHaveCount(count(config('jacques.locales')));
    expect($viewModel->locale())->toEqual('fr_FR');
    expect($viewModel->localeLabel())->toEqual('Français');
    expect(collect($locales)->firstWhere('value', 'fr_FR')['selected'])->toBeTrue();
    expect(collect($locales)->firstWhere('value', 'en')['selected'])->toBeFalse();
});

it('falls back to the language of the application when the one in use is unknown', function () {
    app()->setLocale('kl_GL');

    $user = User::factory()->create(['locale' => null]);

    $viewModel = new PreferencesViewModel(user: $user);

    expect($viewModel->locale())->toEqual(config('app.locale'));
});

it('lists both time formats and ticks the one in use', function () {
    $user = User::factory()->create(['time_format' => TimeFormatEnum::TwelveHour]);

    $viewModel = new PreferencesViewModel(user: $user);

    $formats = $viewModel->timeFormats();

    expect($formats)->toHaveCount(2);
    expect($viewModel->timeFormat())->toEqual(TimeFormatEnum::TwelveHour);
    expect($viewModel->timeFormatLabel())->toEqual('12-hour');
    expect(collect($formats)->firstWhere('value', '12')['selected'])->toBeTrue();
    expect(collect($formats)->firstWhere('value', '24')['selected'])->toBeFalse();
});

it('writes the time the way the account asked for', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 31, 17, 45));

    $user = User::factory()->create(['time_format' => TimeFormatEnum::TwelveHour]);

    $viewModel = new PreferencesViewModel(user: $user);

    expect($viewModel->timePreview())->toEqual('5:45 PM');
});
