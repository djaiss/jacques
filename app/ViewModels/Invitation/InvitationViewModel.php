<?php

declare(strict_types=1);

namespace App\ViewModels\Invitation;

use App\Enums\StaffInvitationStatusEnum;
use App\Models\StaffInvitation;
use App\Models\User;

class InvitationViewModel
{
    public const string MISSING = 'missing';

    public const string ACCEPTED = 'accepted';

    public const string EXPIRED = 'expired';

    public const string REVOKED = 'revoked';

    public const string UNAVAILABLE = 'unavailable';

    public const string OTHER_SALON = 'other_salon';

    public const string CREATE_ACCOUNT = 'create_account';

    public const string SIGN_IN = 'sign_in';

    public const string CONFIRM = 'confirm';

    public const string SWITCH_ACCOUNT = 'switch_account';

    private ?string $variant = null;

    public function __construct(
        private readonly string $token,
        private readonly ?StaffInvitation $invitation,
        private readonly ?User $viewer,
    ) {}

    /**
     * What the page shows: one of the reasons the link no longer works, or
     * what the visitor does next to accept it.
     */
    public function variant(): string
    {
        return $this->variant ??= $this->resolveVariant();
    }

    public function isError(): bool
    {
        return in_array($this->variant(), [
            self::MISSING,
            self::ACCEPTED,
            self::EXPIRED,
            self::REVOKED,
            self::UNAVAILABLE,
            self::OTHER_SALON,
        ], true);
    }

    public function salonName(): string
    {
        return $this->invitation->staff->company->name ?? '';
    }

    public function title(): string
    {
        if ($this->isError()) {
            return match ($this->variant()) {
                self::ACCEPTED => __('This invitation was already used'),
                self::EXPIRED => __('This invitation has expired'),
                self::REVOKED => __('This invitation was withdrawn'),
                self::OTHER_SALON => __('This address already has an account'),
                default => __('This invitation is no longer valid'),
            };
        }

        return __('Join :salon', ['salon' => $this->salonName()]);
    }

    public function message(): string
    {
        return match ($this->variant()) {
            self::ACCEPTED => __('The invitation was accepted. Sign in to reach your workspace.'),
            self::EXPIRED => __('Invitations work for a few days only. Ask :salon to send you a new one.', ['salon' => $this->salonName()]),
            self::REVOKED => __(':salon withdrew this invitation. Ask them if you think this is a mistake.', ['salon' => $this->salonName()]),
            self::OTHER_SALON => __('This email address is already used with another salon. Ask :salon to invite you with another email address.', ['salon' => $this->salonName()]),
            self::MISSING, self::UNAVAILABLE => __('The link was replaced by a newer invitation, or the person it was meant for changed. Ask the salon for a new invitation.'),
            self::SWITCH_ACCOUNT => __('This invitation was sent to :email, but you are signed in as :current. Sign out to continue with the invited address.', [
                'email' => $this->email(),
                'current' => $this->viewer->email ?? '',
            ]),
            default => __('You’ve been invited to join the team as :role.', ['role' => $this->roleName()]),
        };
    }

    public function staffName(): string
    {
        return $this->invitation->staff->full_name ?? '';
    }

    public function email(): string
    {
        return $this->invitation->staff->email ?? '';
    }

    public function roleName(): string
    {
        return $this->invitation->role->name ?? '';
    }

    public function acceptUrl(): string
    {
        return route('invitations.create', ['token' => $this->token]);
    }

    public function signOutUrl(): string
    {
        return route('invitations.signOut.create', ['token' => $this->token]);
    }

    public function signInUrl(): string
    {
        return route('auth.signIn.new');
    }

    private function resolveVariant(): string
    {
        if ($this->invitation === null) {
            return self::MISSING;
        }

        $status = $this->invitation->status();

        if ($status === StaffInvitationStatusEnum::Accepted) {
            return self::ACCEPTED;
        }

        if ($status === StaffInvitationStatusEnum::Revoked) {
            return self::REVOKED;
        }

        if ($status === StaffInvitationStatusEnum::Expired) {
            return self::EXPIRED;
        }

        $staff = $this->invitation->staff;

        if ($status !== StaffInvitationStatusEnum::Pending || $staff->is_active === false || $staff->email === null || $staff->user_id !== null) {
            return self::UNAVAILABLE;
        }

        $account = User::query()->where('email', $staff->email)->first();

        if ($account !== null && $account->company_id !== $staff->company_id) {
            return self::OTHER_SALON;
        }

        if ($this->viewer !== null) {
            return $this->viewer->is($account) ? self::CONFIRM : self::SWITCH_ACCOUNT;
        }

        return $account === null ? self::CREATE_ACCOUNT : self::SIGN_IN;
    }
}
