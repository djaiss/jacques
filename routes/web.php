<?php

declare(strict_types=1);

use App\Http\Controllers\App\Settings\Account\Logs\EmailSentController;
use App\Http\Controllers\App\Settings\Account\Logs\LogController;
use App\Http\Controllers\App\Settings\Account\Preferences\PreferenceController;
use App\Http\Controllers\App\Settings\Account\Profile\AvatarController;
use App\Http\Controllers\App\Settings\Account\Profile\ProfileController;
use App\Http\Controllers\App\Settings\Account\Security\ApiKeyController;
use App\Http\Controllers\App\Settings\Account\Security\PasswordController;
use App\Http\Controllers\App\Settings\Account\Security\RecoveryCodeController;
use App\Http\Controllers\App\Settings\Account\Security\SecurityController;
use App\Http\Controllers\App\Settings\Account\Security\TwoFactorController;
use App\Http\Controllers\App\Settings\Administration\DuplicateRoleController;
use App\Http\Controllers\App\Settings\Administration\LocationArchiveController;
use App\Http\Controllers\App\Settings\Administration\LocationController;
use App\Http\Controllers\App\Settings\Administration\LocationPeopleController;
use App\Http\Controllers\App\Settings\Administration\RoleController;
use App\Http\Controllers\App\Settings\Administration\RolePeopleController;
use App\Http\Controllers\App\Settings\SettingsController;
use App\Http\Controllers\App\WelcomeController;
use Illuminate\Support\Facades\Route;

// Until there is a dashboard to send people to.
Route::get('/', function () {
    return view('welcome');
})->middleware('welcome.screen')->name('home.index');

// The screen a new company sees once, right after signing up.
Route::middleware(['auth', 'set.locale'])->group(function (): void {
    Route::get('welcome', [WelcomeController::class, 'index'])->name('welcome.index');
    Route::put('welcome', [WelcomeController::class, 'update'])->name('welcome.update');
});

Route::middleware(['auth', 'set.locale', 'welcome.screen'])->group(function (): void {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
});

Route::middleware(['auth', 'set.locale', 'welcome.screen'])->group(function (): void {
    Route::get('settings/account/profile', [ProfileController::class, 'index'])->name('settings.profile.index');
    Route::put('settings/account/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::get('settings/account/logs', [LogController::class, 'index'])->name('settings.logs.index');
    Route::get('settings/account/logs/emails', [EmailSentController::class, 'index'])->name('settings.emailsSent.index');

    Route::get('settings/account/preferences', [PreferenceController::class, 'index'])->name('settings.preferences.index');
    Route::put('settings/account/preferences', [PreferenceController::class, 'update'])->name('settings.preferences.update');

    Route::get('settings/account/security', [SecurityController::class, 'index'])->name('settings.security.index');
    Route::put('settings/account/security/password', [PasswordController::class, 'update'])->name('settings.password.update');

    Route::get('settings/account/security/two-factor', [TwoFactorController::class, 'new'])->name('settings.twoFactor.new');
    Route::post('settings/account/security/two-factor', [TwoFactorController::class, 'create'])->name('settings.twoFactor.create');
    Route::delete('settings/account/security/two-factor', [TwoFactorController::class, 'destroy'])->name('settings.twoFactor.destroy');

    Route::post('settings/account/security/recovery-codes', [RecoveryCodeController::class, 'create'])->name('settings.recoveryCodes.create');

    Route::post('settings/account/security/api-keys', [ApiKeyController::class, 'create'])->name('settings.apiKeys.create');
    Route::delete('settings/account/security/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->whereNumber('apiKey')->name('settings.apiKeys.destroy');

    Route::post('settings/account/profile/avatar', [AvatarController::class, 'update'])->name('settings.avatar.update');
    Route::delete('settings/account/profile/avatar', [AvatarController::class, 'destroy'])->name('settings.avatar.destroy');
    Route::get('settings/account/profile/avatar/{employee}/{size}', [AvatarController::class, 'show'])->whereNumber(['employee', 'size'])->name('settings.avatar.show');
});

Route::middleware(['auth', 'set.locale', 'welcome.screen'])->group(function (): void {
    Route::get('settings/administration/roles', [RoleController::class, 'index'])->name('settings.roles.index');
    Route::post('settings/administration/roles', [RoleController::class, 'create'])->name('settings.roles.create');
    Route::get('settings/administration/roles/{role}/{tab?}', [RoleController::class, 'show'])->whereNumber('role')->where('tab', 'people')->name('settings.roles.show');
    Route::put('settings/administration/roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('settings.roles.update');
    Route::delete('settings/administration/roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('settings.roles.destroy');

    Route::post('settings/administration/roles/{role}/duplicate', [DuplicateRoleController::class, 'create'])->whereNumber('role')->name('settings.roleDuplicates.create');

    Route::post('settings/administration/roles/{role}/people', [RolePeopleController::class, 'create'])->whereNumber('role')->name('settings.rolePeople.create');
    Route::delete('settings/administration/roles/{role}/people/{user}', [RolePeopleController::class, 'destroy'])->whereNumber(['role', 'user'])->name('settings.rolePeople.destroy');

    Route::get('settings/administration/locations/{scope?}', [LocationController::class, 'index'])->where('scope', 'archived|all')->name('settings.locations.index');
    Route::post('settings/administration/locations', [LocationController::class, 'create'])->name('settings.locations.create');
    Route::put('settings/administration/locations/{location}', [LocationController::class, 'update'])->whereNumber('location')->name('settings.locations.update');

    Route::post('settings/administration/locations/{location}/archive', [LocationArchiveController::class, 'create'])->whereNumber('location')->name('settings.locationArchives.create');
    Route::delete('settings/administration/locations/{location}/archive', [LocationArchiveController::class, 'destroy'])->whereNumber('location')->name('settings.locationArchives.destroy');

    Route::post('settings/administration/locations/{location}/people', [LocationPeopleController::class, 'create'])->whereNumber('location')->name('settings.locationPeople.create');
    Route::delete('settings/administration/locations/{location}/people/{user}', [LocationPeopleController::class, 'destroy'])->whereNumber(['location', 'user'])->name('settings.locationPeople.destroy');
});
