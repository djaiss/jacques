<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\DetectSignInAddressChange;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\SignInFailedMail;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign somebody in with their email address and their password. Every refusal
 * reports the same message.
 */
class AttemptSignIn
{
    private const MAX_ATTEMPTS = 5;

    private User $user;

    public function __construct(
        private string $email,
        private readonly string $password,
        private readonly bool $remember = false,
        private readonly ?string $ip = null,
    ) {}

    public function execute(): User
    {
        $this->sanitize();
        $this->validate();
        $this->attempt();
        $this->stamp();
        $this->log();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->email = mb_strtolower($this->email);
    }

    private function validate(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many sign-in attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    private function attempt(): void
    {
        $guard = Auth::guard('web');

        // The screen promises a number of days, not the five years Laravel
        // defaults to.
        if ($guard instanceof SessionGuard) {
            $guard->setRememberDuration((int) config('jacques.remember_duration_days') * 24 * 60);
        }

        $candidate = User::query()->where('email', $this->email)->first();

        if ($candidate === null || ! $candidate->is_active || $candidate->usesSingleSignOn()) {
            $this->fail($candidate);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->fail($candidate);
        }

        RateLimiter::clear($this->throttleKey());

        $this->user = Auth::guard('web')->user();
    }

    private function fail(?User $candidate): void
    {
        RateLimiter::hit($this->throttleKey());

        if ($candidate !== null) {
            SendEmail::dispatch(
                mailable: new SignInFailedMail,
                company: $candidate->company,
                emailType: EmailTypeEnum::SignInFailed,
                user: $candidate,
            )->onQueue('high');
        }

        throw ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);
    }

    private function stamp(): void
    {
        $this->user->last_signed_in_at = now();
        $this->user->save();

        DetectSignInAddressChange::dispatch(
            user: $this->user,
            ip: $this->ip ?? '',
        )->onQueue('low');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::UserSignedIn,
        )->onQueue('low');
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->email.'|'.($this->ip ?? ''));
    }
}
