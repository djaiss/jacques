<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffAbsence;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Record a period during which a staff member does not work, at one location
 * they are assigned to, or at every location when none is given. The dates
 * can come in any time zone and are stored in UTC.
 */
class CreateStaffAbsence
{
    private StaffAbsence $absence;

    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly CarbonInterface $startsAt,
        private readonly CarbonInterface $endsAt,
        private readonly ?Location $location = null,
        private ?string $reason = null,
        private ?string $notes = null,
    ) {}

    public function execute(): StaffAbsence
    {
        $this->authorize();
        $this->validate();
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->absence;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ScheduleManage)
            ->forStaff($this->staff)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->endsAt->lessThanOrEqualTo($this->startsAt)) {
            throw new InvalidArgumentException('An absence has to end after it starts');
        }

        if ($this->location === null) {
            return;
        }

        if ($this->location->company_id !== $this->staff->company_id) {
            throw new ModelNotFoundException('Location not found');
        }

        if (! $this->staff->locations()->whereKey($this->location->id)->exists()) {
            throw new InvalidArgumentException('An absence can only concern a location the staff member is assigned to');
        }
    }

    private function sanitize(): void
    {
        $this->reason = TextSanitizer::nullablePlainText($this->reason);
        $this->notes = TextSanitizer::nullablePlainText($this->notes);
    }

    private function create(): void
    {
        $this->absence = StaffAbsence::query()->create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location?->id,
            'starts_at' => $this->startsAt->toMutable()->utc(),
            'ends_at' => $this->endsAt->toMutable()->utc(),
            'reason' => $this->reason,
            'notes' => $this->notes,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffAbsenceCreated,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}
