<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class SecurityViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function usesSingleSignOn(): bool
    {
        return $this->user->usesSingleSignOn();
    }

    public function passwordChangedAt(): ?string
    {
        return $this->user->password_changed_at?->diffForHumans();
    }

    public function usesTwoFactorAuthentication(): bool
    {
        return $this->user->usesTwoFactorAuthentication();
    }

    public function twoFactorConfirmedAt(): ?string
    {
        return $this->user->two_factor_confirmed_at?->diffForHumans();
    }

    /** @return array<int, string> */
    public function recoveryCodes(): array
    {
        return $this->user->two_factor_recovery_codes ?? [];
    }

    /** @return array<int, array{id: int, name: string, createdAt: string, lastUsedAt: ?string}> */
    public function apiKeys(): array
    {
        return $this->user->tokens()
            ->latest()
            ->get()
            ->map(fn (PersonalAccessToken $apiKey): array => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'createdAt' => $apiKey->created_at->diffForHumans(),
                'lastUsedAt' => $apiKey->last_used_at?->diffForHumans(),
            ])
            ->all();
    }

    public function apiKeysHeader(): string
    {
        $count = $this->user->tokens()->count();

        if ($count === 0) {
            return __('Active · no API keys');
        }

        return trans_choice('Active · :count API key|Active · :count API keys', $count);
    }
}
