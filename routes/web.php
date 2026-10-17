<?php

declare(strict_types=1);

use App\Enums\OnboardingStepEnum;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\Onboarding\Availability\StaffHoursController as OnboardingStaffHoursController;
use App\Http\Controllers\App\Onboarding\AvailabilityController as OnboardingAvailabilityController;
use App\Http\Controllers\App\Onboarding\Catalogue\CatalogueController as OnboardingCatalogueController;
use App\Http\Controllers\App\Onboarding\Catalogue\CategoryController as OnboardingCategoryController;
use App\Http\Controllers\App\Onboarding\Catalogue\CategoryPositionController as OnboardingCategoryPositionController;
use App\Http\Controllers\App\Onboarding\Catalogue\RestorationController as OnboardingRestorationController;
use App\Http\Controllers\App\Onboarding\Catalogue\ServiceController as OnboardingCatalogueServiceController;
use App\Http\Controllers\App\Onboarding\Catalogue\ServiceDurationController as OnboardingServiceDurationController;
use App\Http\Controllers\App\Onboarding\Catalogue\ServicePriceController as OnboardingServicePriceController;
use App\Http\Controllers\App\Onboarding\LocationController as OnboardingLocationController;
use App\Http\Controllers\App\Onboarding\OnboardingController;
use App\Http\Controllers\App\Onboarding\ServiceController as OnboardingServiceController;
use App\Http\Controllers\App\Onboarding\StaffController as OnboardingStaffController;
use App\Http\Controllers\App\Onboarding\Team\StaffController as OnboardingTeamStaffController;
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

// The four steps a new salon goes through once the welcome screen is behind it.
Route::middleware(['auth', 'set.locale', 'welcome.screen'])->group(function (): void {
    Route::get('onboarding', [OnboardingController::class, 'index'])->middleware('onboarding.step')->name('onboarding.index');

    Route::get('onboarding/location', [OnboardingLocationController::class, 'index'])->middleware('onboarding.step:'.OnboardingStepEnum::Location->value)->name('onboarding.location.index');
    Route::put('onboarding/location', [OnboardingLocationController::class, 'update'])->middleware('onboarding.step:'.OnboardingStepEnum::Location->value)->name('onboarding.location.update');

    Route::get('onboarding/services', [OnboardingServiceController::class, 'index'])->middleware('onboarding.step:'.OnboardingStepEnum::Services->value)->name('onboarding.services.index');
    Route::put('onboarding/services', [OnboardingServiceController::class, 'update'])->middleware('onboarding.step:'.OnboardingStepEnum::Services->value)->name('onboarding.services.update');

    // The catalogue as step 2 edits it, one small change at a time.
    Route::middleware('onboarding.step:'.OnboardingStepEnum::Services->value)->group(function (): void {
        Route::post('onboarding/services/categories', [OnboardingCategoryController::class, 'create'])->name('onboarding.services.categories.create');
        Route::put('onboarding/services/categories/{category}', [OnboardingCategoryController::class, 'update'])->name('onboarding.services.categories.update');
        Route::delete('onboarding/services/categories/{category}', [OnboardingCategoryController::class, 'destroy'])->name('onboarding.services.categories.destroy');
        Route::put('onboarding/services/categories/{category}/position', [OnboardingCategoryPositionController::class, 'update'])->name('onboarding.services.categoryPositions.update');
        Route::post('onboarding/services/categories/{category}/services', [OnboardingCatalogueServiceController::class, 'create'])->name('onboarding.services.services.create');

        Route::get('onboarding/services/services/{service}/edit', [OnboardingCatalogueServiceController::class, 'edit'])->name('onboarding.services.services.edit');
        Route::put('onboarding/services/services/{service}', [OnboardingCatalogueServiceController::class, 'update'])->name('onboarding.services.services.update');
        Route::delete('onboarding/services/services/{service}', [OnboardingCatalogueServiceController::class, 'destroy'])->name('onboarding.services.services.destroy');
        Route::put('onboarding/services/services/{service}/duration', [OnboardingServiceDurationController::class, 'update'])->name('onboarding.services.serviceDurations.update');
        Route::put('onboarding/services/services/{service}/prices', [OnboardingServicePriceController::class, 'update'])->name('onboarding.services.servicePrices.update');

        Route::post('onboarding/services/catalogue', [OnboardingCatalogueController::class, 'create'])->name('onboarding.services.catalogue.create');
        Route::delete('onboarding/services/catalogue', [OnboardingCatalogueController::class, 'destroy'])->name('onboarding.services.catalogue.destroy');
        Route::post('onboarding/services/restoration', [OnboardingRestorationController::class, 'create'])->name('onboarding.services.restoration.create');
    });

    Route::get('onboarding/staff', [OnboardingStaffController::class, 'index'])->middleware('onboarding.step:'.OnboardingStepEnum::Staff->value)->name('onboarding.staff.index');
    Route::put('onboarding/staff', [OnboardingStaffController::class, 'update'])->middleware('onboarding.step:'.OnboardingStepEnum::Staff->value)->name('onboarding.staff.update');

    // The team as step 3 edits it, one person at a time, in a side panel.
    Route::middleware('onboarding.step:'.OnboardingStepEnum::Staff->value)->group(function (): void {
        Route::get('onboarding/staff/members/new', [OnboardingTeamStaffController::class, 'new'])->name('onboarding.staff.members.new');
        Route::post('onboarding/staff/members', [OnboardingTeamStaffController::class, 'create'])->name('onboarding.staff.members.create');
        Route::get('onboarding/staff/members/{staff}/edit', [OnboardingTeamStaffController::class, 'edit'])->name('onboarding.staff.members.edit');
        Route::put('onboarding/staff/members/{staff}', [OnboardingTeamStaffController::class, 'update'])->name('onboarding.staff.members.update');
        Route::delete('onboarding/staff/members/{staff}', [OnboardingTeamStaffController::class, 'destroy'])->name('onboarding.staff.members.destroy');
    });

    Route::get('onboarding/availability', [OnboardingAvailabilityController::class, 'index'])->middleware('onboarding.step:'.OnboardingStepEnum::Availability->value)->name('onboarding.availability.index');
    Route::put('onboarding/availability', [OnboardingAvailabilityController::class, 'update'])->middleware('onboarding.step:'.OnboardingStepEnum::Availability->value)->name('onboarding.availability.update');

    // The working hours of one team member, in the panel of step 4.
    Route::middleware('onboarding.step:'.OnboardingStepEnum::Availability->value)->group(function (): void {
        Route::get('onboarding/availability/staff/{staff}/edit', [OnboardingStaffHoursController::class, 'edit'])->whereNumber('staff')->name('onboarding.availability.staff.edit');
        Route::put('onboarding/availability/staff/{staff}', [OnboardingStaffHoursController::class, 'update'])->whereNumber('staff')->name('onboarding.availability.staff.update');
    });
});

Route::middleware(['auth', 'set.locale', 'welcome.screen'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
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
    Route::get('settings/account/profile/avatar/{staff}/{size}', [AvatarController::class, 'show'])->whereNumber(['staff', 'size'])->name('settings.avatar.show');
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
    Route::delete('settings/administration/locations/{location}/people/{staff}', [LocationPeopleController::class, 'destroy'])->whereNumber(['location', 'staff'])->name('settings.locationPeople.destroy');
});
