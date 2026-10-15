<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Enums\PermissionGroupEnum;
use App\Enums\ScopeEnum;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Staff;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class RolesViewModel
{
    /** @var Collection<int, Role>|null */
    private ?Collection $roles = null;

    /** @var array<string, ScopeEnum>|null */
    private ?array $grants = null;

    public function __construct(
        private readonly User $user,
        private readonly ?Role $role = null,
        private readonly bool $onPeopleTab = false,
    ) {}

    public function createUrl(): string
    {
        return route('settings.roles.create');
    }

    /** @return array<int, array{id: int, name: string, url: string, summary: string, permissions: string, holders: string, badges: array<int, array{label: string, tone: string}>, hue: int}> */
    public function rows(): array
    {
        return $this->roles()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'url' => route('settings.roles.show', $role->id),
                'summary' => $role->description ?? $this->summary($role),
                /* The noun agrees with the total rather than with the count, and
                   there are always many permissions to grant, so the wording
                   never changes with the number in front of it. */
                'permissions' => trans_choice('[0,*]:count of :total permissions', $role->permissions_count, [
                    'total' => count(PermissionEnum::cases()),
                ]),
                'holders' => $this->holderLabel($role->users_count),
                'badges' => $this->badges($role),
                'hue' => $this->hue($role),
            ])
            ->all();
    }

    /** @return array{id: int, name: string, description: string, slug: string, isEditable: bool, badges: array<int, array{label: string, tone: string}>, hue: int, updateUrl: string, destroyUrl: string, duplicateUrl: string, assignUrl: string}|null */
    public function role(): ?array
    {
        if ($this->role === null) {
            return null;
        }

        return [
            'id' => $this->role->id,
            'name' => old('name', $this->role->name),
            'description' => old('description', $this->role->description ?? ''),
            'slug' => $this->role->slug,
            'isEditable' => $this->role->is_editable,
            'badges' => $this->badges($this->role),
            'hue' => $this->hue($this->role),
            'updateUrl' => route('settings.roles.update', $this->role->id),
            'destroyUrl' => route('settings.roles.destroy', $this->role->id),
            'duplicateUrl' => route('settings.roleDuplicates.create', $this->role->id),
            'assignUrl' => route('settings.rolePeople.create', $this->role->id),
        ];
    }

    /** @return array<int, array{key: string, label: string, url: string, current: bool}> */
    public function tabs(): array
    {
        if ($this->role === null) {
            return [];
        }

        return [
            [
                'key' => 'permissions',
                'label' => $this->permissionTabLabels()[$this->grantedCount()],
                'url' => route('settings.roles.show', $this->role->id),
                'current' => ! $this->onPeopleTab,
            ],
            [
                'key' => 'people',
                'label' => trans_choice('[0,*]People · :count', $this->role->users()->count()),
                'url' => route('settings.roles.show', [$this->role->id, 'people']),
                'current' => $this->onPeopleTab,
            ],
        ];
    }

    public function onPeopleTab(): bool
    {
        return $this->onPeopleTab;
    }

    /** @return array<int, array{title: string, note: string, count: string, counts: array<int, string>, width: string, tone: string, permissions: array<int, array{value: string, label: string, granted: bool, scope: string, scopes: array<string, string>}>}> */
    public function groups(): array
    {
        $groups = [];

        foreach (PermissionGroupEnum::cases() as $group) {
            $permissions = array_values(array_map(
                fn (PermissionEnum $permission): array => $this->permission($permission),
                array_filter(
                    PermissionEnum::cases(),
                    fn (PermissionEnum $permission): bool => $permission->group() === $group,
                ),
            ));

            $granted = count(array_filter($permissions, fn (array $permission): bool => $permission['granted']));
            $ratio = $granted / count($permissions);
            $counts = $this->countLabels(count($permissions));

            $groups[] = [
                'title' => __($group->label()),
                'note' => __($group->note()),
                'count' => $counts[$granted],
                'counts' => $counts,
                'width' => $granted === 0 ? '9px' : round($ratio * 100).'%',
                'tone' => match (true) {
                    $granted === 0 => 'none',
                    $ratio < 1 => 'partial',
                    default => 'full',
                },
                'permissions' => $permissions,
            ];
        }

        return $groups;
    }

    /** @return array<string, bool> */
    public function grantedPermissions(): array
    {
        $granted = [];

        foreach (PermissionEnum::cases() as $permission) {
            $granted[$permission->value] = $this->permission($permission)['granted'];
        }

        return $granted;
    }

    public function grantCountLabel(): string
    {
        return $this->grantCountLabels()[$this->grantedCount()];
    }

    /** @return array<int, string> */
    public function grantCountLabels(): array
    {
        return $this->countLabels(count(PermissionEnum::cases()));
    }

    /** @return array<int, string> */
    public function permissionTabLabels(): array
    {
        $labels = [];

        foreach (range(0, count(PermissionEnum::cases())) as $count) {
            $labels[$count] = trans_choice('[0,*]Permissions · :count', $count);
        }

        return $labels;
    }

    public function warnsAboutAdministration(): bool
    {
        return array_key_exists(PermissionEnum::RoleManage->value, $this->grants());
    }

    /** @return array<int, array{id: int, name: string, email: string, staff: Staff|null, since: string, removeUrl: string, isRemovable: bool}> */
    public function people(): array
    {
        if ($this->role === null) {
            return [];
        }

        $heldSince = $this->heldSince($this->role);
        $isLastOwner = $this->role->slug === Role::OWNER
            && $this->role->users()->where('is_active', true)->count() === 1;

        return $this->role->users()
            ->with('staff')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->staff->name ?? $user->email,
                'email' => $user->email,
                'staff' => $user->staff,
                'since' => __('Holds it since :date', ['date' => $heldSince[$user->id]->isoFormat('MMM YYYY')]),
                'removeUrl' => route('settings.rolePeople.destroy', [$this->role->id, $user->id]),
                'isRemovable' => ! ($isLastOwner && $user->is_active),
            ])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, email: string, staff: Staff|null}> */
    public function assignable(): array
    {
        if ($this->role === null) {
            return [];
        }

        return $this->user->company->users()
            ->whereDoesntHave('roles', fn ($query) => $query->where('roles.id', $this->role->id))
            ->with('staff')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->staff->name ?? $user->email,
                'email' => $user->email,
                'staff' => $user->staff,
            ])
            ->all();
    }

    public function canBeDeleted(): bool
    {
        return $this->deleteHint() === null;
    }

    public function deleteHint(): ?string
    {
        if ($this->role === null || ! $this->role->is_editable) {
            return __('not editable');
        }

        $holders = $this->role->users()->count();

        if ($holders > 0) {
            return trans_choice('[0,*]held by :count', $holders);
        }

        return null;
    }

    private function grantedCount(): int
    {
        return count(array_filter($this->grantedPermissions()));
    }

    /** @return array<int, string> */
    private function countLabels(int $total): array
    {
        $labels = [];

        foreach (range(0, $total) as $count) {
            $labels[$count] = trans_choice('[0,*]:count of :total granted', $count, ['total' => $total]);
        }

        return $labels;
    }

    /** @return array<int, Carbon> */
    private function heldSince(Role $role): array
    {
        return UserRole::query()
            ->where('role_id', $role->id)
            ->get()
            ->mapWithKeys(fn (UserRole $held): array => [$held->user_id => $held->created_at])
            ->all();
    }

    /** @return array{value: string, label: string, granted: bool, scope: string, scopes: array<string, string>} */
    private function permission(PermissionEnum $permission): array
    {
        $submitted = old('permissions');
        $grants = $this->grants();

        $granted = $submitted === null
            ? array_key_exists($permission->value, $grants)
            : isset($submitted[$permission->value]['granted']);

        $scope = $submitted === null
            ? ($grants[$permission->value] ?? ScopeEnum::Company)->value
            : ($submitted[$permission->value]['scope'] ?? ScopeEnum::Company->value);

        $scopes = [];

        foreach ($permission->scopes() as $candidate) {
            $scopes[$candidate->value] = __($candidate->label());
        }

        return [
            'value' => $permission->value,
            'label' => __($permission->label()),
            'granted' => $granted,
            'scope' => $scope,
            'scopes' => count($scopes) > 1 ? $scopes : [],
        ];
    }

    /** @return Collection<int, Role> */
    private function roles(): Collection
    {
        return $this->roles ??= $this->user->company->roles()
            ->with('permissions')
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();
    }

    /** @return array<string, ScopeEnum> */
    private function grants(): array
    {
        if ($this->grants !== null) {
            return $this->grants;
        }

        if ($this->role === null) {
            return $this->grants = [];
        }

        return $this->grants = $this->role->permissions
            ->mapWithKeys(fn (RolePermission $grant): array => [$grant->permission->value => $grant->scope])
            ->all();
    }

    private function summary(Role $role): string
    {
        if ($role->permissions_count === 0) {
            return __('Nothing yet. Tick what it is allowed to do.');
        }

        if ($role->permissions_count === count(PermissionEnum::cases())) {
            return __('Everything, including handing out roles.');
        }

        $granted = $role->permissions
            ->map(fn (RolePermission $grant): PermissionGroupEnum => $grant->permission->group())
            ->unique();

        $groups = array_map(
            fn (PermissionGroupEnum $group): string => __($group->label()),
            array_filter(
                PermissionGroupEnum::cases(),
                fn (PermissionGroupEnum $group): bool => $granted->contains($group),
            ),
        );

        return Arr::join(array_values($groups), ', ', ' '.__('and').' ');
    }

    private function holderLabel(int $holders): string
    {
        if ($holders === 0) {
            return __('nobody');
        }

        return trans_choice(':count person|:count people', $holders);
    }

    /** @return array<int, array{label: string, tone: string}> */
    private function badges(Role $role): array
    {
        $badges = [];

        if ($this->administers($role)) {
            $badges[] = ['label' => __('Full access'), 'tone' => 'accent'];
        }

        if (! $role->is_editable) {
            $badges[] = ['label' => __('Not editable'), 'tone' => 'neutral'];
        }

        return $badges;
    }

    private function hue(Role $role): int
    {
        return $this->administers($role) ? 30 : 80;
    }

    private function administers(Role $role): bool
    {
        return $role->permissions
            ->contains(fn (RolePermission $grant): bool => $grant->permission === PermissionEnum::RoleManage);
    }
}
