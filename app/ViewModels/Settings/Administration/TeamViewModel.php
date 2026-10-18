<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Enums\PermissionGroupEnum;
use App\Enums\StaffInvitationStatusEnum;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TeamViewModel
{
    /** @var Collection<int, Staff>|null */
    private ?Collection $staff = null;

    /** @var Collection<int, Role>|null */
    private ?Collection $roles = null;

    public function __construct(
        private readonly User $user,
        private readonly ?Staff $selected = null,
    ) {}

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    /**
     * One row per active member of the team shown on this tab.
     *
     * @return list<array{id: int, name: string, email: string|null, staff: Staff, isViewer: bool, role: string|null, state: string, tone: string, manageUrl: string}>
     */
    public function rows(): array
    {
        $tab = $this->tab();

        return $this->staff()->filter(fn (Staff $member): bool => $tab === null || $this->filter($member) === $tab)->map(fn (Staff $member): array => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'staff' => $member,
            'isViewer' => $member->user_id === $this->user->id,
            'role' => $this->role($member)?->name,
            'state' => $this->state($member),
            'tone' => $this->tone($member),
            'manageUrl' => route('settings.team.show', $member->id),
        ])->values()->all();
    }

    /** @return list<array{label: string, count: int, url: string, isCurrent: bool}> */
    public function tabs(): array
    {
        $filters = $this->staff()->map(fn (Staff $member): string => $this->filter($member));
        $tabs = [
            [null, __('Everybody'), route('settings.team.index')],
            ['signs_in', __('Signs in'), route('settings.teamSignsIn.index')],
            ['invited', __('Invited'), route('settings.teamInvited.index')],
            ['no_account', __('No account'), route('settings.teamNoAccount.index')],
        ];

        return array_map(fn (array $tab): array => [
            'label' => $tab[1],
            'count' => $tab[0] === null ? $filters->count() : $filters->filter(fn (string $filter): bool => $filter === $tab[0])->count(),
            'url' => $tab[2],
            'isCurrent' => $tab[0] === $this->tab(),
        ], $tabs);
    }

    protected function tab(): ?string
    {
        return null;
    }

    /**
     * What the side panel of the selected person shows: where their account
     * stands, the actions that state allows, and the role they hold.
     *
     * @return array{id: int, name: string, email: string|null, state: string, tone: string, sentence: string, actions: list<array{kind: string, label: string, url: string, method: string, confirm: bool, primary: bool, withRole: bool}>, roles: list<array{id: int, name: string, description: string|null, disabled: bool}>, permissions: array<int, list<array{title: string, permissions: list<array{label: string, allowed: bool, scope: string|null}>}>>, roleId: int|null, isSoleOwner: bool, canSave: bool, roleUrl: string, roleEditorUrls: array<int, string>, closeUrl: string}|null
     */
    public function panel(): ?array
    {
        if ($this->selected === null) {
            return null;
        }

        $member = $this->staff()->firstWhere('id', $this->selected->id) ?? $this->selected;
        $isSoleOwner = $this->isSoleOwner($member);

        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'state' => $this->state($member),
            'tone' => $this->tone($member),
            'sentence' => $this->sentence($member),
            'actions' => $this->actions($member),
            'roles' => $this->roles()->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'disabled' => $isSoleOwner && $role->slug !== Role::OWNER,
            ])->values()->all(),
            'permissions' => $this->roles()->mapWithKeys(fn (Role $role): array => [
                $role->id => $this->permissions($role),
            ])->all(),
            'roleId' => $member->user_id === null
                ? $this->role($member)->id ?? $this->defaultRole()?->id
                : $this->role($member)?->id,
            'isSoleOwner' => $isSoleOwner,
            'canSave' => $member->user_id !== null || $member->invitation !== null,
            'roleUrl' => route('settings.teamRoles.update', $member->id),
            'roleEditorUrls' => $this->roles()->mapWithKeys(fn (Role $role): array => [
                $role->id => route('settings.roles.show', $role->id),
            ])->all(),
            'closeUrl' => route('settings.team.index'),
        ];
    }

    /** @return Collection<int, Staff> */
    private function staff(): Collection
    {
        return $this->staff ??= Staff::query()
            ->where('company_id', $this->user->company_id)
            ->where('is_active', true)
            ->with(['invitation.role', 'user.roles'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    private function role(Staff $member): ?Role
    {
        if ($member->user !== null) {
            return $member->user->roles->first();
        }

        return $member->invitation?->role;
    }

    private function filter(Staff $member): string
    {
        if ($member->user_id !== null) {
            return 'signs_in';
        }

        return match ($member->invitation?->status()) {
            StaffInvitationStatusEnum::Prepared,
            StaffInvitationStatusEnum::Pending,
            StaffInvitationStatusEnum::Expired => 'invited',
            default => 'no_account',
        };
    }

    private function state(Staff $member): string
    {
        if ($member->user_id !== null) {
            return __('Signs in');
        }

        return match ($member->invitation?->status()) {
            StaffInvitationStatusEnum::Prepared => __('Invitation not sent'),
            StaffInvitationStatusEnum::Pending => __('Invitation sent'),
            StaffInvitationStatusEnum::Expired => __('Invitation expired'),
            default => __('No account'),
        };
    }

    private function tone(Staff $member): string
    {
        if ($member->user_id !== null) {
            return 'positive';
        }

        return match ($member->invitation?->status()) {
            StaffInvitationStatusEnum::Prepared,
            StaffInvitationStatusEnum::Pending => 'attention',
            StaffInvitationStatusEnum::Expired => 'muted',
            default => 'neutral',
        };
    }

    private function sentence(Staff $member): string
    {
        $invitation = $member->invitation;

        if ($member->user_id !== null) {
            return __('Signs in with their own account.');
        }

        return match ($invitation?->status()) {
            StaffInvitationStatusEnum::Prepared => __('Invitation ready since :date. Not sent yet.', ['date' => $invitation->updated_at?->isoFormat('LL')]),
            StaffInvitationStatusEnum::Pending => __('Invitation sent on :date. Awaiting acceptance.', ['date' => $invitation->sent_at?->isoFormat('LL')]),
            StaffInvitationStatusEnum::Expired => __('The invitation sent on :date expired before it was accepted.', ['date' => $invitation->sent_at?->isoFormat('LL')]),
            default => $member->email === null
                ? __('Add an email address to their profile to invite them.')
                : __('No invitation yet. Without an account, they can still be booked.'),
        };
    }

    /** @return list<array{kind: string, label: string, url: string, method: string, confirm: bool, primary: bool, withRole: bool}> */
    private function actions(Staff $member): array
    {
        $sending = route('settings.staffInvitationSendings.create', $member->id);
        $invitation = route('settings.staffInvitations.destroy', $member->id);

        if ($member->user_id !== null) {
            if (! $this->user->isOwner() || $member->user_id === $this->user->id) {
                return [];
            }

            return [
                ['kind' => 'remove', 'label' => __('Remove the account'), 'url' => route('settings.teamAccounts.destroy', $member->id), 'method' => 'delete', 'confirm' => true, 'primary' => false, 'withRole' => false],
            ];
        }

        if ($member->email === null) {
            return [];
        }

        return match ($member->invitation?->status()) {
            StaffInvitationStatusEnum::Prepared => [
                ['kind' => 'send', 'label' => __('Send the invitation'), 'url' => $sending, 'method' => 'post', 'confirm' => false, 'primary' => true, 'withRole' => false],
                ['kind' => 'cancel', 'label' => __('Cancel the invitation'), 'url' => $invitation, 'method' => 'delete', 'confirm' => true, 'primary' => false, 'withRole' => false],
            ],
            StaffInvitationStatusEnum::Pending => [
                ['kind' => 'send', 'label' => __('Send it again'), 'url' => $sending, 'method' => 'post', 'confirm' => false, 'primary' => false, 'withRole' => false],
                ['kind' => 'cancel', 'label' => __('Cancel the invitation'), 'url' => $invitation, 'method' => 'delete', 'confirm' => true, 'primary' => false, 'withRole' => false],
            ],
            StaffInvitationStatusEnum::Expired => [
                ['kind' => 'send', 'label' => __('Send a new invitation'), 'url' => $sending, 'method' => 'post', 'confirm' => false, 'primary' => true, 'withRole' => false],
            ],
            default => [
                ['kind' => 'send', 'label' => __('Send an invitation'), 'url' => $sending, 'method' => 'post', 'confirm' => false, 'primary' => true, 'withRole' => true],
                ['kind' => 'prepare', 'label' => __('Prepare it for later'), 'url' => route('settings.staffInvitations.create', $member->id), 'method' => 'post', 'confirm' => false, 'primary' => false, 'withRole' => true],
            ],
        };
    }

    /** @return list<array{title: string, permissions: list<array{label: string, allowed: bool, scope: string|null}>}> */
    private function permissions(Role $role): array
    {
        $grants = $role->permissions->keyBy(fn (RolePermission $grant): string => $grant->permission->value);
        $groups = [];

        foreach (PermissionGroupEnum::cases() as $group) {
            $permissions = array_filter(
                PermissionEnum::cases(),
                fn (PermissionEnum $permission): bool => $permission->group() === $group,
            );

            $groups[] = [
                'title' => __($group->label()),
                'permissions' => array_values(array_map(fn (PermissionEnum $permission): array => [
                    'label' => __($permission->label()),
                    'allowed' => $grants->has($permission->value),
                    'scope' => $grants->has($permission->value) && count($permission->scopes()) > 1
                        ? __($grants[$permission->value]->scope->label())
                        : null,
                ], $permissions)),
            ];
        }

        return $groups;
    }

    private function isSoleOwner(Staff $member): bool
    {
        if ($member->user === null || ! $member->user->roles->contains('slug', Role::OWNER)) {
            return false;
        }

        return ! User::query()
            ->where('company_id', $this->user->company_id)
            ->where('id', '!=', $member->user_id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('slug', Role::OWNER))
            ->exists();
    }

    private function defaultRole(): ?Role
    {
        return $this->roles()->firstWhere('slug', Role::STAFF) ?? $this->roles()->first();
    }

    /** @return Collection<int, Role> */
    private function roles(): Collection
    {
        return $this->roles ??= Role::query()
            ->where('company_id', $this->user->company_id)
            ->with('permissions')
            ->orderBy('id')
            ->get();
    }
}
