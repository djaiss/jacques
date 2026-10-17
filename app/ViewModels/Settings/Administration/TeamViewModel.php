<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\StaffInvitationStatusEnum;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TeamViewModel
{
    /** @var Collection<int, Role>|null */
    private ?Collection $roles = null;

    public function __construct(
        private readonly User $user,
    ) {}

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    /**
     * One row per active member of the team, with where their invitation
     * stands and only the actions that state allows.
     *
     * @return list<array{id: int, name: string, email: string|null, role: string|null, status: string|null, tone: string, sentence: string, actions: list<array{kind: string, label: string, url: string, method: string, confirm: bool}>, canPrepare: bool, roleId: int|null}>
     */
    public function rows(): array
    {
        $staff = Staff::query()
            ->where('company_id', $this->user->company_id)
            ->where('is_active', true)
            ->with(['invitation.role', 'user.roles'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return $staff->map(fn (Staff $member): array => $this->row($member))->values()->all();
    }

    /** @return list<array{id: int, name: string}> */
    public function roleOptions(): array
    {
        return $this->roles()->map(fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
        ])->values()->all();
    }

    /**
     * @return array{id: int, name: string, email: string|null, role: string|null, status: string|null, tone: string, sentence: string, actions: list<array{kind: string, label: string, url: string, method: string, confirm: bool}>, canPrepare: bool, roleId: int|null}
     */
    private function row(Staff $member): array
    {
        $invitation = $member->invitation;
        $status = $invitation?->status();
        $role = $invitation->role ?? $member->user?->roles->first();
        $defaultRole = $this->roles()->firstWhere('slug', Role::STAFF) ?? $this->roles()->first();
        $canPrepare = $member->user_id === null && $member->email !== null
            && ($status === null || $status === StaffInvitationStatusEnum::Revoked);

        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $role?->name,
            'status' => $this->statusLabel($member, $status),
            'tone' => $this->tone($member, $status),
            'sentence' => $this->sentence($member, $status),
            'actions' => $this->actions($member, $status, $role),
            'canPrepare' => $canPrepare,
            'roleId' => $invitation->role_id ?? $defaultRole?->id,
        ];
    }

    private function statusLabel(Staff $member, ?StaffInvitationStatusEnum $status): ?string
    {
        if ($status !== null) {
            return __($status->label());
        }

        return $member->user_id === null ? null : __('Has an account');
    }

    private function tone(Staff $member, ?StaffInvitationStatusEnum $status): string
    {
        if ($status === null) {
            return $member->user_id === null ? 'neutral' : 'positive';
        }

        return match ($status) {
            StaffInvitationStatusEnum::Accepted => 'positive',
            StaffInvitationStatusEnum::Pending => 'attention',
            StaffInvitationStatusEnum::Prepared => 'neutral',
            StaffInvitationStatusEnum::Expired, StaffInvitationStatusEnum::Revoked => 'muted',
        };
    }

    private function sentence(Staff $member, ?StaffInvitationStatusEnum $status): string
    {
        $invitation = $member->invitation;

        return match (true) {
            $status === StaffInvitationStatusEnum::Prepared => __('Invitation ready since :date. Not sent yet.', ['date' => $invitation->updated_at?->isoFormat('LL')]),
            $status === StaffInvitationStatusEnum::Pending => __('Invitation sent on :date. Awaiting acceptance.', ['date' => $invitation->sent_at?->isoFormat('LL')]),
            $status === StaffInvitationStatusEnum::Expired => __('The invitation sent on :date expired before it was accepted.', ['date' => $invitation->sent_at?->isoFormat('LL')]),
            $status === StaffInvitationStatusEnum::Revoked => __('Invitation revoked on :date.', ['date' => $invitation->revoked_at?->isoFormat('LL')]),
            $status === StaffInvitationStatusEnum::Accepted => __('Joined on :date.', ['date' => $invitation->accepted_at?->isoFormat('LL')]),
            $member->user_id !== null => __('Signs in with their own account.'),
            $member->email === null => __('Add an email address to their profile to invite them.'),
            default => __('No invitation yet. Without an account, they can still be booked.'),
        };
    }

    /**
     * @return list<array{kind: string, label: string, url: string, method: string, confirm: bool}>
     */
    private function actions(Staff $member, ?StaffInvitationStatusEnum $status, ?Role $role): array
    {
        $sending = route('settings.staffInvitationSendings.create', $member->id);
        $invitation = route('settings.staffInvitations.destroy', $member->id);
        $revocation = route('settings.staffInvitationRevocations.create', $member->id);

        if ($member->user_id !== null) {
            return $role === null ? [] : [
                ['kind' => 'manage', 'label' => __('Manage access'), 'url' => route('settings.roles.show', ['role' => $role->id, 'tab' => 'people']), 'method' => 'get', 'confirm' => false],
            ];
        }

        return match ($status) {
            StaffInvitationStatusEnum::Prepared => [
                ['kind' => 'send', 'label' => __('Send'), 'url' => $sending, 'method' => 'post', 'confirm' => false],
                ['kind' => 'cancel', 'label' => __('Cancel'), 'url' => $invitation, 'method' => 'delete', 'confirm' => true],
            ],
            StaffInvitationStatusEnum::Pending => [
                ['kind' => 'resend', 'label' => __('Resend'), 'url' => $sending, 'method' => 'post', 'confirm' => false],
                ['kind' => 'revoke', 'label' => __('Revoke'), 'url' => $revocation, 'method' => 'post', 'confirm' => true],
            ],
            StaffInvitationStatusEnum::Expired => [
                ['kind' => 'send', 'label' => __('Send new invitation'), 'url' => $sending, 'method' => 'post', 'confirm' => false],
            ],
            default => [],
        };
    }

    /** @return Collection<int, Role> */
    private function roles(): Collection
    {
        return $this->roles ??= Role::query()->where('company_id', $this->user->company_id)->orderBy('id')->get();
    }
}
