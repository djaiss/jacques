<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\StaffAbsence;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Change the dates, the location, the reason or the notes of an absence. The
 * same rules as when it was recorded apply, and the dates are stored in UTC.
 */
class UpdateStaffAbsence
{
    public function __construct(
        private readonly User $author,
        private readonly StaffAbsence $absence,
        private readonly CarbonInterface $startsAt,
        private readonly CarbonInterface $endsAt,
        private readonly ?Location $location,
        private ?string $reason,
        private ?string $notes,
    ) {}

    public function execute(): StaffAbsence
    {
        $this->authorize();
        $this->validate();
        $this->sanitize();
        $this->update();
        $this->log();

        return $this->absence;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ScheduleManage)
            ->forStaff($this->absence->staff)
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

        if ($this->location->company_id !== $this->absence->staff->company_id) {
            throw new ModelNotFoundException('Location not found');
        }

        if (! $this->absence->staff->locations()->whereKey($this->location->id)->exists()) {
            throw new InvalidArgumentException('An absence can only concern a location the staff member is assigned to');
        }
    }

    private function sanitize(): void
    {
        $this->reason = TextSanitizer::nullablePlainText($this->reason);
        $this->notes = TextSanitizer::nullablePlainText($this->notes);
    }

    private function update(): void
    {
        $this->absence->starts_at = $this->startsAt->toMutable()->utc();
        $this->absence->ends_at = $this->endsAt->toMutable()->utc();
        $this->absence->location_id = $this->location?->id;
        $this->absence->reason = $this->reason;
        $this->absence->notes = $this->notes;
        $this->absence->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->absence->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffAbsenceUpdated,
            parameters: ['name' => $this->absence->staff->name],
        )->onQueue('low');
    }
}
