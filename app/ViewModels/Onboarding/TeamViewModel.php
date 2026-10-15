<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Helpers\CatalogueText;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TeamViewModel extends StepViewModel
{
    /** @var Collection<int, Staff>|null */
    private ?Collection $team = null;

    /** @var Collection<int, LocationService>|null */
    private ?Collection $offered = null;

    public function __construct(
        User $user,
        OnboardingStepEnum $step,
        private readonly ?Staff $openStaff = null,
        private readonly bool $isAddingStaff = false,
    ) {
        parent::__construct(user: $user, step: $step);
    }

    public function title(): string
    {
        return __('Who works at :salon?', ['salon' => $this->salonName()]);
    }

    public function introduction(): string
    {
        return __('You are already on the team, and you offer every service you added. Add the people who work with you and choose which services each of them provides.');
    }

    public function newStaffUrl(): string
    {
        return route('onboarding.staff.members.new');
    }

    public function summary(): string
    {
        if ($this->colleagueCount() === 0) {
            return __('Just you for now. Add the people who take appointments or help run the salon.');
        }

        return trans_choice(':count person at :salon.|:count people at :salon.', $this->loadedTeam()->count(), ['salon' => $this->salonName()]);
    }

    public function colleagueCount(): int
    {
        return $this->loadedTeam()->filter(fn (Staff $staff): bool => $staff->user_id !== $this->user->id)->count();
    }

    public function invitationCount(): int
    {
        return $this->loadedTeam()->filter(fn (Staff $staff): bool => $staff->invitation !== null)->count();
    }

    public function footerStatus(): string
    {
        if ($this->invitationCount() > 0) {
            return trans_choice(':count invitation ready.|:count invitations ready.', $this->invitationCount());
        }

        if ($this->colleagueCount() === 0) {
            return __('You can add your team now or later from Settings.');
        }

        return __('No invitations prepared.');
    }

    /** @return list<array<string, mixed>> */
    public function rows(): array
    {
        return $this->loadedTeam()->values()->map(fn (Staff $staff, int $index): array => [
            'id' => $staff->id,
            'name' => $staff->name,
            'isOwnRecord' => $staff->user_id === $this->user->id,
            'subtitle' => implode(' · ', array_filter([
                $staff->name !== $staff->full_name ? $staff->full_name : null,
                $staff->job_title,
                $staff->email ?? __('No email address'),
            ])),
            'services' => $this->servicesSummary($staff),
            'hasNoService' => $staff->is_bookable && $this->performedIds($staff) === [],
            'account' => $this->account($staff),
            'hasInvitation' => $staff->invitation !== null,
            'hue' => [30, 80, 200, 250, 310][$index % 5],
            'editUrl' => route('onboarding.staff.members.edit', $staff),
        ])->all();
    }

    public function panel(): ?StaffPanel
    {
        if (! $this->isAddingStaff && $this->openStaff === null) {
            return null;
        }

        $staff = $this->openStaff === null ? null : $this->loadedTeam()->firstWhere('id', $this->openStaff->id);

        return new StaffPanel(
            staff: $staff,
            author: $this->user,
            salonName: $this->salonName(),
            categories: $this->categories(),
            selectedIds: $staff === null ? [] : $this->performedIds($staff),
            roles: Role::query()->where('company_id', $this->user->company_id)->orderBy('id')->get(),
        );
    }

    /**
     * The services offered at the headquarters, grouped by category in the
     * order of the catalogue, the ones without a category last.
     *
     * @return list<array{name: string, services: list<array{id: int, name: string}>}>
     */
    private function categories(): array
    {
        $languages = $this->user->company->languages;

        return $this->loadedOffered()
            ->groupBy(fn (LocationService $offered): int => $offered->service->service_category_id ?? 0)
            ->sortBy(fn ($group, int $categoryId): int => $categoryId === 0 ? PHP_INT_MAX : $group->first()->service->category->position)
            ->map(fn ($group, int $categoryId): array => [
                'name' => $categoryId === 0
                    ? __('Without a category')
                    : CatalogueText::resolve($group->first()->service->category->name ?? [], $languages, app()->getLocale()),
                'services' => $group->map(fn (LocationService $offered): array => [
                    'id' => $offered->id,
                    'name' => CatalogueText::resolve($offered->service->name ?? [], $languages, app()->getLocale()),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function servicesSummary(Staff $staff): string
    {
        $count = count($this->performedIds($staff));
        $total = $this->loadedOffered()->count();

        return match (true) {
            ! $staff->is_bookable => __('Not bookable'),
            $count === 0 => __('None yet'),
            $count === $total => trans_choice('All :count service|All :count services', $count),
            default => trans_choice(':count service|:count services', $count),
        };
    }

    private function account(Staff $staff): string
    {
        return match (true) {
            $staff->user_id === $this->user->id => __('Account owner'),
            $staff->user_id !== null => __('Has an account'),
            $staff->invitation !== null => __('Invitation ready'),
            $staff->email !== null => __('Not invited'),
            default => __('No email, no account'),
        };
    }

    /** @return list<int> */
    private function performedIds(Staff $staff): array
    {
        $offeredIds = $this->loadedOffered()->pluck('id')->all();

        return $staff->locationServices
            ->pluck('id')
            ->filter(fn (int $id): bool => in_array($id, $offeredIds, true))
            ->values()
            ->all();
    }

    /** @return Collection<int, Staff> */
    private function loadedTeam(): Collection
    {
        return $this->team ??= Staff::query()
            ->where('company_id', $this->user->company_id)
            ->where('is_active', true)
            ->with(['invitation', 'locationServices'])
            ->orderBy('position')
            ->get()
            ->sortBy(fn (Staff $staff): int => $staff->user_id === $this->user->id ? 0 : 1)
            ->values();
    }

    /** @return Collection<int, LocationService> */
    private function loadedOffered(): Collection
    {
        return $this->offered ??= LocationService::query()
            ->where('location_id', $this->headquarters()?->id)
            ->where('is_active', true)
            ->whereHas('service', fn ($query) => $query->where('is_active', true))
            ->with('service.category')
            ->get()
            ->sortBy(fn (LocationService $offered): int => $offered->service->position)
            ->values();
    }

    private function headquarters(): ?Location
    {
        return Location::query()
            ->where('company_id', $this->user->company_id)
            ->where('is_headquarters', true)
            ->first();
    }
}
