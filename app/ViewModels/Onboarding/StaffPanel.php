<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Helpers\StaffInvitationCopy;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class StaffPanel
{
    /**
     * @param  list<array{name: string, services: list<array{id: int, name: string}>}>  $categories
     * @param  list<int>  $selectedIds
     * @param  Collection<int, Role>  $roles
     */
    public function __construct(
        private readonly ?Staff $staff,
        private readonly User $author,
        private readonly string $salonName,
        private readonly array $categories,
        private readonly array $selectedIds,
        private readonly Collection $roles,
    ) {}

    public function key(): string
    {
        return $this->staff === null ? 'new' : (string) $this->staff->id;
    }

    public function isNew(): bool
    {
        return $this->staff === null;
    }

    public function isOwnRecord(): bool
    {
        return $this->staff !== null && $this->staff->user_id === $this->author->id;
    }

    public function canBeInvited(): bool
    {
        return ! $this->isOwnRecord() && $this->staff?->user_id === null;
    }

    public function eyebrow(): string
    {
        return match (true) {
            $this->isNew() => __('Add a team member'),
            $this->isOwnRecord() => __('Your profile'),
            default => __('Edit team member'),
        };
    }

    public function salonName(): string
    {
        return $this->salonName;
    }

    public function newMemberTitle(): string
    {
        return __('New team member');
    }

    public function formAction(): string
    {
        return $this->staff === null
            ? route('onboarding.staff.members.create')
            : route('onboarding.staff.members.update', $this->staff);
    }

    public function destroyUrl(): ?string
    {
        if ($this->staff === null || $this->isOwnRecord()) {
            return null;
        }

        return route('onboarding.staff.members.destroy', $this->staff);
    }

    public function removalQuestion(): string
    {
        return __(':name leaves the team and loses their services. Sure?', ['name' => $this->staff->name ?? '']);
    }

    /**
     * What the form starts with: what was just submitted when it failed, the
     * stored record otherwise.
     *
     * @return array{first_name: string, last_name: string, display_name: string, job_title: string, email: string, phone: string, bio: string, is_bookable: bool, services: list<int>, invite: bool, role: int|null}
     */
    public function values(): array
    {
        $defaultRole = $this->roles->firstWhere('slug', Role::STAFF) ?? $this->roles->first();
        $invitation = $this->staff?->invitation;

        $stored = [
            'first_name' => $this->staff->first_name ?? '',
            'last_name' => $this->staff->last_name ?? '',
            'display_name' => $this->staff->display_name ?? '',
            'job_title' => $this->staff->job_title ?? '',
            'email' => $this->staff->email ?? '',
            'phone' => $this->staff->phone ?? '',
            'bio' => $this->staff->bio ?? '',
            'is_bookable' => $this->staff->is_bookable ?? true,
            'services' => $this->selectedIds,
            'invite' => $this->staff === null || $invitation !== null,
            'role' => $invitation->role_id ?? $defaultRole?->id,
        ];

        if (! session()->hasOldInput()) {
            return $stored;
        }

        return [
            'first_name' => (string) old('first_name'),
            'last_name' => (string) old('last_name'),
            'display_name' => (string) old('display_name'),
            'job_title' => (string) old('job_title'),
            'email' => $this->isOwnRecord() ? $stored['email'] : (string) old('email'),
            'phone' => (string) old('phone'),
            'bio' => (string) old('bio'),
            'is_bookable' => (bool) old('is_bookable'),
            'services' => array_map(intval(...), (array) old('services', [])),
            'invite' => (bool) old('invite'),
            'role' => old('role') === null ? $stored['role'] : (int) old('role'),
        ];
    }

    /** @return list<array{name: string, services: list<array{id: int, name: string}>}> */
    public function categories(): array
    {
        return $this->categories;
    }

    public function serviceTotal(): int
    {
        return array_sum(array_map(fn (array $category): int => count($category['services']), $this->categories));
    }

    /**
     * The sentence above the services, with :selected left for the page to fill
     * in as boxes are ticked.
     */
    public function selectionSentence(): string
    {
        return trans_choice(':selected of :count service selected|:selected of :count services selected', $this->serviceTotal());
    }

    public function fullSelectionSentence(): string
    {
        return trans_choice('All :count service selected|All :count services selected', $this->serviceTotal());
    }

    /** @return list<array{id: int, name: string}> */
    public function roleOptions(): array
    {
        return $this->roles->map(fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
        ])->values()->all();
    }

    /**
     * The sentences of the email preview, read from the copy of the email
     * itself. The first name is left as a placeholder the page fills in from
     * what is typed, since the preview follows the form before it is saved.
     *
     * @return array<string, string>
     */
    public function preview(): array
    {
        $inviter = $this->author->staff->name ?? $this->author->email;
        $copy = new StaffInvitationCopy(
            inviter: $inviter,
            salon: $this->salonName,
            days: (int) config('jacques.staff_invitation_duration_days'),
        );

        return [
            'title' => __('The invitation :first_name will receive'),
            'titleWithoutName' => __('The invitation they will receive'),
            'from' => "{$this->salonName} <".config('mail.from.address').'>',
            'subject' => $copy->subject(),
            'heading' => $copy->title(),
            'greeting' => $copy->greeting(':first_name'),
            'greetingWithoutName' => __('Hello,'),
            'body' => $copy->body(),
            'purpose' => $copy->purpose(),
            'salonLabel' => $copy->salonLabel(),
            'roleLabel' => $copy->roleLabel(),
            'button' => $copy->button(),
            'expiry' => $copy->expiry(),
            'footer' => $copy->footer(),
            'when' => __('Not sent yet. You send invitations once the onboarding is finished, or later from Settings.'),
        ];
    }
}
