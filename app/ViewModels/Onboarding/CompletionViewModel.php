<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Models\StaffInvitation;
use App\Models\User;

class CompletionViewModel
{
    private ?int $preparedCount = null;

    public function __construct(
        private readonly User $user,
    ) {}

    public function salonName(): string
    {
        return $this->user->company->name;
    }

    public function title(): string
    {
        return __(':salon is ready', ['salon' => $this->salonName()]);
    }

    public function hasPreparedInvitations(): bool
    {
        return $this->preparedCount() > 0;
    }

    public function preparedCount(): int
    {
        return $this->preparedCount ??= StaffInvitation::query()
            ->whereNull('sent_at')
            ->whereNull('revoked_at')
            ->whereNull('accepted_at')
            ->whereHas('staff', fn ($query) => $query
                ->where('company_id', $this->user->company_id)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->whereNull('user_id'))
            ->count();
    }

    public function invitationSentence(): string
    {
        return trans_choice(':count invitation is ready to send.|:count invitations are ready to send.', $this->preparedCount());
    }

    public function sendLabel(): string
    {
        return trans_choice('Send :count invitation|Send :count invitations', $this->preparedCount());
    }
}
