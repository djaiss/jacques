<?php

declare(strict_types=1);

namespace App\Helpers;

class StaffInvitationCopy
{
    public function __construct(
        private readonly string $inviter,
        private readonly string $salon,
        private readonly int $days,
    ) {}

    public function subject(): string
    {
        return __(':inviter has invited you to join :salon', ['inviter' => $this->inviter, 'salon' => $this->salon]);
    }

    public function title(): string
    {
        return __('You’ve been invited to join :salon', ['salon' => $this->salon]);
    }

    public function greeting(string $firstName): string
    {
        return __('Hi :first_name,', ['first_name' => $firstName]);
    }

    public function body(): string
    {
        return __(':inviter has invited you to join the team at :salon on :app.', [
            'inviter' => $this->inviter,
            'salon' => $this->salon,
            'app' => config('app.name'),
        ]);
    }

    public function purpose(): string
    {
        return __(':app helps you manage your appointments, schedule and clients.', ['app' => config('app.name')]);
    }

    public function salonLabel(): string
    {
        return __('Salon');
    }

    public function roleLabel(): string
    {
        return __('Your role');
    }

    public function button(): string
    {
        return __('Accept invitation');
    }

    public function expiry(): string
    {
        return trans_choice('This invitation expires in :count day.|This invitation expires in :count days.', $this->days);
    }

    public function footer(): string
    {
        return __('If you weren’t expecting this invitation, you can ignore this email.');
    }
}
