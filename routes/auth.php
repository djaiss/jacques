<?php

declare(strict_types=1);

use App\Http\Controllers\App\Auth\EmailVerificationController;
use App\Http\Controllers\App\Auth\LocaleController;
use App\Http\Controllers\App\Auth\LocalSignInController;
use App\Http\Controllers\App\Auth\MagicLinkController;
use App\Http\Controllers\App\Auth\NewPasswordController;
use App\Http\Controllers\App\Auth\PasswordResetLinkController;
use App\Http\Controllers\App\Auth\RegistrationController;
use App\Http\Controllers\App\Auth\ScreenshotLinkController;
use App\Http\Controllers\App\Auth\SignInController;
use App\Http\Controllers\App\Auth\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('register', [RegistrationController::class, 'new'])->name('auth.register.new');
    Route::post('register', [RegistrationController::class, 'create'])->name('auth.register.create');
});

Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('sign-in', [SignInController::class, 'new'])->name('auth.signIn.new');
    Route::post('sign-in', [SignInController::class, 'create'])->name('auth.signIn.create');
});

// Only ever registered on a machine somebody develops on.
Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::post('local-sign-in', [LocalSignInController::class, 'create'])->name('auth.localSignIn.create');
});

// Answers only on a development instance with screenshot links switched on.
Route::middleware(['signed', 'set.locale'])->group(function (): void {
    Route::get('screenshot-link', [ScreenshotLinkController::class, 'show'])->name('auth.screenshotLink.show');
});

Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('send-magic-link', [MagicLinkController::class, 'new'])->name('auth.magicLink.new');

    Route::post('send-magic-link', [MagicLinkController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.magicLink.create');

    Route::get('magic-link/{token}', [MagicLinkController::class, 'show'])
        ->middleware('throttle:6,1')
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('auth.magicLink.show');
});

Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('forgot-password', [PasswordResetLinkController::class, 'new'])->name('auth.password.new');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.password.create');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'edit'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('auth.password.edit');

    Route::post('reset-password', [NewPasswordController::class, 'update'])->name('auth.password.update');
});

Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'new'])->name('auth.twoFactor.new');

    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.twoFactor.create');
});

Route::middleware(['auth', 'set.locale'])->group(function (): void {
    Route::get('verify-email', [EmailVerificationController::class, 'show'])->name('auth.verification.notice');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->where('id', '[0-9]+')
        ->where('hash', '[a-f0-9]{40}')
        ->name('auth.verification.verify');

    Route::post('resend-verification-email', [EmailVerificationController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.verification.send');
});

Route::post('sign-out', [SignInController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.signIn.destroy');

Route::put('locale', [LocaleController::class, 'update'])
    ->middleware('set.locale')
    ->name('auth.locale.update');
